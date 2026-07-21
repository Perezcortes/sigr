<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Models\Municipality;

class MunicipalitySeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('data/municipios.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("Archivo {$jsonPath} no encontrado.");
            return;
        }

        $municipios = json_decode(File::get($jsonPath), true);

        if (!$municipios) {
            $this->command->error("El archivo JSON está vacío o mal formado.");
            return;
        }

        DB::table('municipalities')->truncate();

        $seen = [];
        $dataToInsert = [];

        foreach ($municipios as $m) {
            if (!isset($m['name'], $m['state_id'])) continue;

            $key = $m['state_id'] . '|' . mb_strtolower(trim($m['name']));
            if (isset($seen[$key])) continue;

            $seen[$key] = true;
            $dataToInsert[] = [
                'name'       => trim($m['name']),
                'state_id'   => $m['state_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($dataToInsert, 500) as $chunk) {
            Municipality::insert($chunk);
        }

        $this->command->info("Insertados " . count($dataToInsert) . " municipios correctamente.");
    }
}
