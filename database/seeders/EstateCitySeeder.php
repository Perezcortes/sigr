<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Estate;

class EstateCitySeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 
            'Chiapas', 'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 
            'Durango', 'Estado de México', 'Guanajuato', 'Guerrero', 'Hidalgo', 
            'Jalisco', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 
            'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 
            'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas'
        ];

        $this->command->info('Iniciando carga de estados...');

        foreach ($estados as $nombreEstado) {
            Estate::firstOrCreate(['nombre' => $nombreEstado]);
        }
        
        $this->command->info('Estados cargados correctamente.');
    }
}