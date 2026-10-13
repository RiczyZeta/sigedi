<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

/** HU02 - Gestión de usuarios y roles. */
class UserManagementTest extends TestCase
{
    use RefreshDatabase, CreaUsuarios;

    public function test_administrador_ve_el_listado_de_usuarios(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $otro = $this->crearUsuario(Role::TECNICO, ['email' => 'otro@ina54.test']);

        $this->actingAs($admin)->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('otro@ina54.test');
    }

    public function test_tecnico_y_usuario_reciben_403_en_el_modulo_de_usuarios(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);

        $this->actingAs($tecnico)->get(route('usuarios.index'))->assertForbidden();
        $this->actingAs($docente)->get(route('usuarios.index'))->assertForbidden();
        $this->actingAs($docente)->get(route('usuarios.create'))->assertForbidden();
    }

    public function test_administrador_crea_un_usuario_con_rol(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $rolTecnico = Role::firstOrCreate(['nombre' => Role::TECNICO]);

        $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Nuevo Técnico',
            'email' => 'nuevo@ina54.test',
            'role_id' => $rolTecnico->id,
            'whatsapp' => '+51987654321',
            'password' => 'ClaveSegura1*',
            'password_confirmation' => 'ClaveSegura1*',
        ])->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('users', ['email' => 'nuevo@ina54.test', 'role_id' => $rolTecnico->id]);
    }

    public function test_no_permite_correo_duplicado(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $this->crearUsuario(Role::USUARIO, ['email' => 'repetido@ina54.test']);
        $rol = Role::firstOrCreate(['nombre' => Role::USUARIO]);

        $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Duplicado',
            'email' => 'repetido@ina54.test',
            'role_id' => $rol->id,
            'password' => 'ClaveSegura1*',
            'password_confirmation' => 'ClaveSegura1*',
        ])->assertSessionHasErrors('email');
    }

    public function test_administrador_edita_un_usuario_sin_cambiar_su_contrasena(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $usuario = $this->crearUsuario(Role::USUARIO, ['email' => 'editar@ina54.test']);
        $hashAnterior = $usuario->password;

        $this->actingAs($admin)->put(route('usuarios.update', $usuario), [
            'name' => 'Nombre Editado',
            'email' => 'editar@ina54.test',
            'role_id' => $usuario->role_id,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('usuarios.index'));

        $usuario->refresh();
        $this->assertSame('Nombre Editado', $usuario->name);
        $this->assertSame($hashAnterior, $usuario->password);
    }

    public function test_administrador_desactiva_y_reactiva_un_usuario(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $usuario = $this->crearUsuario(Role::TECNICO);

        $this->actingAs($admin)->patch(route('usuarios.estado', $usuario));
        $this->assertFalse($usuario->fresh()->activo);

        $this->actingAs($admin)->patch(route('usuarios.estado', $usuario));
        $this->assertTrue($usuario->fresh()->activo);
    }

    public function test_el_administrador_no_puede_desactivarse_a_si_mismo(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);

        $this->actingAs($admin)->patch(route('usuarios.estado', $admin))
            ->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_el_administrador_no_puede_cambiar_su_propio_rol(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $rolTecnico = Role::firstOrCreate(['nombre' => Role::TECNICO]);

        $this->actingAs($admin)->put(route('usuarios.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role_id' => $rolTecnico->id,
        ])->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->esAdministrador());
    }

    public function test_el_listado_pagina_de_a_10(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        for ($i = 0; $i < 12; $i++) {
            $this->crearUsuario(Role::USUARIO);
        }

        $this->actingAs($admin)->get(route('usuarios.index'))
            ->assertOk()
            ->assertViewHas('usuarios', fn ($p) => $p->count() === 10 && $p->total() === 13);
    }
}
