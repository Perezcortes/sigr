<?php

namespace App\Models;

// use CWSPS154\UsersRolesPermissions\Models\HasRole;
use App\Models\Traits\HasHashId;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    use HasHashId;
    use HasRoles;
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'email',
        'mobile',
        'telefono',
        'whatsapp',
        'facebook',
        'instagram',
        'linkedin',
        'about_me',
        'id_nocnok',
        'zone_estate_id',
        'zone_city_ids',
        'password',
        // 'role_id',
        'last_seen',
        'is_active',
        'is_owner',
        'is_tenant',
        'is_buyer',
        'is_seller',
        'office_id',
        'asesor_id',
        'score',
        'evolution_whatsapp_instance_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'zone_city_ids' => 'array',
            'is_active' => 'boolean',
            'is_owner' => 'boolean',
            'is_tenant' => 'boolean',
            'is_buyer' => 'boolean',
            'is_seller' => 'boolean',
            'score' => 'integer',
        ];
    }

    /**
     * Determina quién puede acceder al panel de Filament.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Solo los usuarios con estos roles pueden ver la pantalla de login o entrar al panel
        return $this->hasAnyRole(['Administrador', 'Gerente', 'Agente']);
    }

    /**
     * URL de imagen por defecto para avatares
     */
    protected const DEFAULT_AVATAR_URL = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png';

    /**
     * Obtener la URL del avatar del usuario
     * Si no hay avatar o hay un error, retorna una imagen por defecto
     */
    public function getFilamentAvatarUrl(): ?string
    {
        try {
            $avatarUrl = $this->getFirstMediaUrl('profile-images');

            // Si no hay avatar, usar imagen por defecto
            if (empty($avatarUrl)) {
                return self::DEFAULT_AVATAR_URL;
            }

            // Verificar si la URL es válida (no contiene localhost o errores)
            // Si contiene localhost, usar imagen por defecto para evitar problemas
            if (str_contains($avatarUrl, 'localhost') || str_contains($avatarUrl, '127.0.0.1')) {
                return self::DEFAULT_AVATAR_URL;
            }

            return $avatarUrl;
        } catch (\Exception $e) {
            // Si hay cualquier error, usar imagen por defecto
            return self::DEFAULT_AVATAR_URL;
        }
    }

    /**
     * Relación con Applications
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Relación con Properties
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /**
     * Relación con Tenant
     */
    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class);
    }

    /**
     * Relación con Office
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function evolutionWhatsappInstance(): BelongsTo
    {
        return $this->belongsTo(WhatsappInstance::class, 'evolution_whatsapp_instance_id');
    }

    /**
     * Asesor asignado al usuario (típicamente rol Cliente).
     */
    public function assignedAsesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /**
     * Clientes que tienen asignado a este usuario como asesor.
     *
     * @return HasMany<User, User>
     */
    public function clientesAsignados(): HasMany
    {
        return $this->hasMany(User::class, 'asesor_id');
    }

    /**
     * Relación con Owner (Propietario)
     * Necesaria para que funcione el filtro "whereHas('owner')"
     */
    public function owner(): HasOne
    {
        return $this->hasOne(Owner::class);
    }

    public function zoneEstate(): BelongsTo
    {
        return $this->belongsTo(Estate::class, 'zone_estate_id');
    }

    public function zoneCities()
    {
        $cityIds = array_filter((array) ($this->zone_city_ids ?? []));

        return Municipality::query()->whereIn('id', $cityIds)->get();
    }
}
