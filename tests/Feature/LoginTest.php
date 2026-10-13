<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

/** HU01 - Iniciar sesión. */
class LoginTest extends TestCase
{
    use RefreshDatabase, CreaUsuarios;

    public function test_muestra_el_formulario_de_login(): void
    {
        $this->get('/login')->assertOk()->assertSee('Iniciar sesión');
    }

    public function test_credenciales_validas_acceden_al_panel(): void
    {
        $usuario = $this->crearUsuario(Role::ADMINISTRADOR, [
            'email' => 'admin@ina54.test',
            'password' => 'Secreta123*',
        ]);

        $this->post('/login', ['email' => 'admin@ina54.test', 'password' => 'Secreta123*'])
            ->assertRedirect(route('panel'));

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_credenciales_invalidas_muestran_error_y_no_ingresan(): void
    {
        $this->crearUsuario(Role::TECNICO, ['email' => 'tec@ina54.test']);

        $this->post('/login', ['email' => 'tec@ina54.test', 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_inactivo_no_puede_ingresar(): void
    {
        $this->crearUsuario(Role::USUARIO, [
            'email' => 'inactivo@ina54.test',
            'password' => 'Secreta123*',
            'activo' => false,
        ]);

        $this->post('/login', ['email' => 'inactivo@ina54.test', 'password' => 'Secreta123*'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_visitante_es_redirigido_al_login(): void
    {
        $this->get('/panel')->assertRedirect(route('login'));
    }

    public function test_usuario_autenticado_ve_el_panel_y_puede_cerrar_sesion(): void
    {
        $usuario = $this->crearUsuario(Role::TECNICO);

        $this->actingAs($usuario)->get('/panel')->assertOk()->assertSee('Bienvenido');

        $this->actingAs($usuario)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_usuario_desactivado_con_sesion_abierta_es_expulsado(): void
    {
        $inactivo = $this->crearUsuario(Role::USUARIO, ['activo' => false]);

        $this->actingAs($inactivo)->get('/panel')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
