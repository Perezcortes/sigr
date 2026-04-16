<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\Estate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdvisorSearchController extends Controller
{
    public function suggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('query', ''));

        if ($query === '') {
            return response()->json([
                'data' => [
                    'estates' => [],
                    'cities' => [],
                    'advisors' => [],
                ],
            ]);
        }

        $estates = Estate::query()
            ->where('nombre', 'like', "%{$query}%")
            ->orderBy('nombre')
            ->limit(10)
            ->get(['id', 'nombre'])
            ->map(fn (Estate $estate) => [
                'type' => 'estate',
                'id' => $estate->id,
                'name' => $estate->nombre,
            ])
            ->values();

        $cities = Municipality::query()
            ->with('estate:id,nombre')
            ->where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'state_id'])
            ->map(fn (Municipality $city) => [
                'type' => 'city',
                'id' => $city->id,
                'name' => $city->name,
                'estate_id' => $city->state_id,
                'estate_name' => $city->estate?->nombre,
            ])
            ->values();

        $advisors = $this->baseAdvisorQuery()
            ->where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn (User $advisor) => [
                'type' => 'advisor',
                'hash_id' => $advisor->hash_id,
                'user_id_hashed' => $advisor->hash_id,
                'name' => $advisor->name,
            ])
            ->values();

        return response()->json([
            'data' => [
                'estates' => $estates,
                'cities' => $cities,
                'advisors' => $advisors,
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:255'],
            'estate_id' => ['nullable', 'integer', 'exists:estates,id'],
            'state_id' => ['nullable', 'integer', 'exists:estates,id'],
            'zone_state_id' => ['nullable', 'integer', 'exists:estates,id'],
            'city_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $queryText = trim((string) ($validated['query'] ?? ''));
        $estateId = $validated['estate_id'] ?? $validated['state_id'] ?? $validated['zone_state_id'] ?? null;
        $cityId = $validated['city_id'] ?? null;
        $name = trim((string) ($validated['name'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 20);
        $queryEstateIds = [];
        $queryCityIds = [];

        $advisorsQuery = $this->baseAdvisorQuery()
            ->when($estateId, fn (Builder $q) => $this->applyStateFilter($q, (int) $estateId))
            ->when($cityId, fn (Builder $q) => $q->where(function ($query) use ($cityId) {
                $query->whereJsonContains('zone_city_ids', (int) $cityId)
                      ->orWhereJsonContains('zone_city_ids', (string) $cityId);
            }))
            ->when($name !== '', fn (Builder $q) => $q->where('name', 'like', "%{$name}%"));

        if ($queryText !== '') {
            $queryEstateIds = Estate::query()
                ->where('nombre', 'like', "%{$queryText}%")
                ->pluck('id')
                ->all();

            $queryCityIds = Municipality::query()
                ->where('name', 'like', "%{$queryText}%")
                ->pluck('id')
                ->all();

            $advisorsQuery->where(function (Builder $q) use ($queryText, $queryEstateIds, $queryCityIds) {
                $q->where('name', 'like', "%{$queryText}%");

                if ($queryEstateIds !== []) {
                    $this->applyStateIdsFilter($q, $queryEstateIds);
                }

                foreach ($queryCityIds as $cityIdFromQuery) {
                    $q->orWhereJsonContains('zone_city_ids', (int) $cityIdFromQuery)
                      ->orWhereJsonContains('zone_city_ids', (string) $cityIdFromQuery);
                }
            });
        }

        $results = $advisorsQuery
            ->orderBy('name')
            ->paginate($perPage)
            ->through(function (User $advisor) use ($queryText, $estateId, $cityId, $name, $queryEstateIds, $queryCityIds) {
                $resolvedStateId = $this->resolveStateId($advisor);
                $advisorCityIds = array_map('intval', (array) ($advisor->zone_city_ids ?? []));
                $matchedBy = [];

                if ($name !== '' && str_contains(mb_strtolower($advisor->name), mb_strtolower($name))) {
                    $matchedBy[] = 'name';
                }

                if ($estateId !== null && $resolvedStateId === (int) $estateId) {
                    $matchedBy[] = 'state';
                }

                if ($cityId !== null && in_array((int) $cityId, $advisorCityIds, true)) {
                    $matchedBy[] = 'city';
                }

                if ($queryText !== '') {
                    if (str_contains(mb_strtolower($advisor->name), mb_strtolower($queryText))) {
                        $matchedBy[] = 'name';
                    }

                    if ($resolvedStateId !== null && in_array($resolvedStateId, array_map('intval', $queryEstateIds), true)) {
                        $matchedBy[] = 'state';
                    }

                    if (array_intersect($advisorCityIds, array_map('intval', $queryCityIds)) !== []) {
                        $matchedBy[] = 'city';
                    }
                }

                $matchedBy = array_values(array_unique($matchedBy));

                return $this->formatAdvisorData($advisor, $resolvedStateId, $matchedBy);
            });

        return response()->json($results);
    }

    public function details(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'matched_by' => ['required', 'string', 'in:name,state,city'],
            'name' => ['nullable', 'string', 'max:255'],
            'hash_id' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $matchedBy = $validated['matched_by'];
        $name = trim((string) ($validated['name'] ?? ''));
        $hashId = trim((string) ($validated['hash_id'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 20);

        $advisorsQuery = $this->baseAdvisorQuery();

        if ($matchedBy === 'name') {
            if ($hashId !== '') {
                $id = User::decodeId($hashId);
                if ($id !== null) {
                    $advisorsQuery->where('id', $id);
                } else {
                    $advisorsQuery->where('name', $name);
                }
            } else {
                $advisorsQuery->where('name', $name);
            }
        } elseif ($matchedBy === 'state') {
            $stateIds = Estate::query()
                ->where('nombre', 'like', "%{$name}%")
                ->pluck('id')
                ->all();

            if (empty($stateIds)) {
                $advisorsQuery->whereRaw('1 = 0');
            } else {
                $advisorsQuery->where(function (Builder $q) use ($stateIds) {
                    $this->applyStateIdsFilter($q, $stateIds);
                });
            }
        } elseif ($matchedBy === 'city') {
            $cityIds = Municipality::query()
                ->where('name', 'like', "%{$name}%")
                ->pluck('id')
                ->all();

            if (empty($cityIds)) {
                $advisorsQuery->whereRaw('1 = 0');
            } else {
                $advisorsQuery->where(function (Builder $q) use ($cityIds) {
                    foreach ($cityIds as $cityId) {
                        $q->orWhereJsonContains('zone_city_ids', (int) $cityId)
                          ->orWhereJsonContains('zone_city_ids', (string) $cityId);
                    }
                });
            }
        }

        $results = $advisorsQuery
            ->orderBy('name')
            ->paginate($perPage)
            ->through(function (User $advisor) use ($matchedBy) {
                $resolvedStateId = $this->resolveStateId($advisor);
                return $this->formatAdvisorData($advisor, $resolvedStateId, [$matchedBy]);
            });

        return response()->json($results);
    }

    private function baseAdvisorQuery(): Builder
    {
        return User::query()
            ->with([
                'zoneEstate:id,nombre',
            ])
            ->where('is_active', true)
            ->whereHas('roles', function (Builder $q) {
                $q->where('name', 'Asesor')->orWhere('id', 3);
            });
    }

    private function applyStateFilter(Builder $query, int $stateId): Builder
    {
        return $query->where(function (Builder $q) use ($stateId) {
            if (Schema::hasColumn('users', 'zone_estate_id')) {
                $q->where('zone_estate_id', $stateId);
            }

            if (Schema::hasColumn('users', 'zone_state_id')) {
                $q->orWhere('zone_state_id', $stateId);
            }
        });
    }

    /**
     * @param  array<int, int|string>  $stateIds
     */
    private function applyStateIdsFilter(Builder $query, array $stateIds): Builder
    {
        $normalizedStateIds = array_values(array_map('intval', $stateIds));

        return $query->orWhere(function (Builder $q) use ($normalizedStateIds) {
            if (Schema::hasColumn('users', 'zone_estate_id')) {
                $q->whereIn('zone_estate_id', $normalizedStateIds);
            }

            if (Schema::hasColumn('users', 'zone_state_id')) {
                $q->orWhereIn('zone_state_id', $normalizedStateIds);
            }
        });
    }

    private function resolveStateId(User $advisor): ?int
    {
        $stateId = $advisor->zone_estate_id;

        if ($stateId !== null) {
            return (int) $stateId;
        }

        if (Schema::hasColumn('users', 'zone_state_id')) {
            $legacyStateId = $advisor->getAttribute('zone_state_id');

            return $legacyStateId !== null ? (int) $legacyStateId : null;
        }

        return null;
    }
    /**
     * @param array<int, string> $matchedBy
     * @return array<string, mixed>
     */
    private function formatAdvisorData(User $advisor, ?int $resolvedStateId, array $matchedBy): array
    {
        return [
            'hash_id' => $advisor->hash_id,
            'user_id_hashed' => $advisor->hash_id,
            'name' => $advisor->name,
            'email' => $advisor->email,
            'telefono' => $advisor->telefono,
            'whatsapp' => $advisor->whatsapp,
            'facebook' => $advisor->facebook,
            'instagram' => $advisor->instagram,
            'linkedin' => $advisor->linkedin,
            'about_me' => $advisor->about_me,
            'id_nocnok' => $advisor->id_nocnok,
            'avatar_url' => $advisor->getFilamentAvatarUrl(),
            'zone_state_id' => $resolvedStateId,
            'zone_estate_id' => $resolvedStateId,
            'zone_estate_name' => $advisor->zoneEstate?->nombre
                ?? ($resolvedStateId !== null ? Estate::query()->whereKey($resolvedStateId)->value('nombre') : null),
            'zone_city_ids' => $advisor->zone_city_ids ?? [],
            'matched_by' => $matchedBy,
            'zone_cities' => $advisor->zoneCities()
                ->map(fn (Municipality $city) => [
                    'id' => $city->id,
                    'name' => $city->name,
                ])
                ->values(),
        ];
    }
}
