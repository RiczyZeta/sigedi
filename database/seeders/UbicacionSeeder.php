<?php

namespace Database\Seeders;

use App\Models\Ubicacion;
use Illuminate\Database\Seeder;

class UbicacionSeeder extends Seeder
{
    /**
     * AJUSTAR: estos ambientes son de ejemplo. Reemplázalos por los ambientes
     * reales de la I.E. INA 54 que verifiques durante el diagnóstico (R02).
     */
    public function run(): void
    {
        $ambientes = [
            'Taller de Computación',
            'Dirección',
            'Secretaría',
            'Sala de Docentes',
            'Aula de Innovación',
            'Almacén de equipos',
        ];

        foreach ($ambientes as $nombre) {
            Ubicacion::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
