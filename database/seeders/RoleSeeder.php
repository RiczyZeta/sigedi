<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            Role::ADMINISTRADOR => 'Gestiona usuarios, asigna incidencias y accede a todos los módulos.',
            Role::TECNICO => 'Registra dispositivos, atiende incidencias y documenta actividades.',
            Role::USUARIO => 'Docente o administrativo que reporta y consulta sus incidencias.',
        ];

        foreach ($roles as $nombre => $descripcion) {
            Role::updateOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }
    }
}
