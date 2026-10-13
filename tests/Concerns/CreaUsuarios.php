<?php

namespace Tests\Concerns;

use App\Models\Role;
use App\Models\User;

trait CreaUsuarios
{
    protected function crearUsuario(string $rol, array $atributos = []): User
    {
        $role = Role::firstOrCreate(['nombre' => $rol]);

        return User::create(array_merge([
            'role_id' => $role->id,
            'name' => ucfirst($rol).' de prueba',
            'email' => $rol.'.'.uniqid().'@ina54.test',
            'password' => 'Password123*',
            'activo' => true,
        ], $atributos));
    }
}
