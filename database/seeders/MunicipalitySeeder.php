<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Models\Municipality;
use App\Models\Estate;

class MunicipalitySeeder extends Seeder
{
    // INEGI code → nombre exacto como está en la tabla estates
    private const INEGI_MAP = [
        1  => 'Aguascalientes',
        2  => 'Baja California',
        3  => 'Baja California Sur',
        4  => 'Campeche',
        5  => 'Coahuila',
        6  => 'Colima',
        7  => 'Chiapas',
        8  => 'Chihuahua',
        9  => 'Ciudad de México',
        10 => 'Durango',
        11 => 'Guanajuato',
        12 => 'Guerrero',
        13 => 'Hidalgo',
        14 => 'Jalisco',
        15 => 'Estado de México',
        16 => 'Michoacán',
        17 => 'Morelos',
        18 => 'Nayarit',
        19 => 'Nuevo León',
        20 => 'Oaxaca',
        21 => 'Puebla',
        22 => 'Querétaro',
        23 => 'Quintana Roo',
        24 => 'San Luis Potosí',
        25 => 'Sinaloa',
        26 => 'Sonora',
        27 => 'Tabasco',
        28 => 'Tamaulipas',
        29 => 'Tlaxcala',
        30 => 'Veracruz',
        31 => 'Yucatán',
        32 => 'Zacatecas',
    ];

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

        // Cargar estates indexados por nombre para lookup rápido
        $estateIds = Estate::pluck('id', 'nombre')->all();

        DB::table('municipalities')->truncate();
        $this->command->info("Tabla municipalities limpiada.");

        $seen = [];
        $dataToInsert = [];
        $skipped = 0;

        foreach ($municipios as $m) {
            if (!isset($m['name'], $m['state_id'])) {
                $skipped++;
                continue;
            }

            $nombreEstado = self::INEGI_MAP[$m['state_id']] ?? null;
            $estateId = $nombreEstado ? ($estateIds[$nombreEstado] ?? null) : null;

            if (!$estateId) {
                $skipped++;
                continue;
            }

            $name = trim($m['name']);
            $key = $estateId . '|' . mb_strtolower($name);

            if (isset($seen[$key])) {
                $skipped++;
                continue;
            }

            $seen[$key] = true;
            $dataToInsert[] = [
                'name'       => $name,
                'state_id'   => $estateId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($dataToInsert, 500) as $chunk) {
            Municipality::insert($chunk);
        }

        $this->command->info("Insertados " . count($dataToInsert) . " municipios correctamente.");

        if ($skipped > 0) {
            $this->command->warn("Omitidos: {$skipped} registros sin state_id o nombre reconocido.");
        }
    }
}
