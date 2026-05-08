<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\Municipality;

class MunicipalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/municipios.json');
        
        if (!File::exists($jsonPath)) {
            $this->command->error("Archivo {$jsonPath} no encontrado.");
            return;
        }

        $jsonContent = File::get($jsonPath);
        $municipios = json_decode($jsonContent, true);

        if (!$municipios) {
            $this->command->error("El archivo JSON está vacío o mal formado.");
            return;
        }

        $this->command->info("Insertando " . count($municipios) . " municipios...");

        $chunks = array_chunk($municipios, 500);

        $inserted = 0;
        foreach ($chunks as $chunk) {
            $dataToInsert = [];
            foreach ($chunk as $m) {
                if (isset($m['name']) && isset($m['state_id'])) {
                    $dataToInsert[] = [
                        'name' => trim($m['name']),
                        'state_id' => $m['state_id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            
            if (count($dataToInsert) > 0) {
                // Insert OR ignore might be better if re-running, but insert is faster
                // We'll use upsert to avoid duplicates if re-rerunning
                Municipality::upsert(
                    $dataToInsert,
                    ['name', 'state_id'], // unique columns (optional, needs unique constraint on table)
                    ['updated_at']
                );
                $inserted += count($dataToInsert);
            }
        }
        
        $this->command->info("Se han insertado {$inserted} municipios correctamente.");
    }
}
