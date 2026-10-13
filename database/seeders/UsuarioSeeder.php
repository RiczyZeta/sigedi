<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    /**
     * IMPORTANTE: cambia la contraseña del administrador después del primer ingreso
     * y elimina los usuarios de demostración antes de entregar el sistema.
     */
    public function run(): void
    {
        $admin = Role::where('nombre', Role::ADMINISTRADOR)->firstOrFail();
        $tecnico = Role::where('nombre', Role::TECNICO)->firstOrFail();
        $usuario = Role::where('nombre', Role::USUARIO)->firstOrFail();

        User::firstOrCreate(['email' => 'admin@ina54.edu.pe'], [
            'role_id' => $admin->id,
            'name' => 'Administrador SIGEDI',
            'password' => 'Admin2026*',
        ]);

        // Usuarios de demostración (solo entorno local)
        if (app()->environment('local')) {
            User::firstOrCreate(['email' => 'tecnico@ina54.edu.pe'], [
                'role_id' => $tecnico->id,
                'name' => 'Técnico de Demostración',
                'password' => 'Tecnico2026*',
            ]);

            User::firstOrCreate(['email' => 'docente@ina54.edu.pe'], [
                'role_id' => $usuario->id,
                'name' => 'Docente de Demostración',
                'password' => 'Docente2026*',
            ]);
        }
    }
}
