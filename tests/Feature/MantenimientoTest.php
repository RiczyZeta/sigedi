<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Dispositivo;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

/** HU14 - Registro manual de mantenimientos preventivos y correctivos (Sprint 4). */
class MantenimientoTest extends TestCase
{
    use RefreshDatabase, CreaUsuarios;

    private function dispositivo(string $estado = 'operativo'): Dispositivo
    {
        return Dispositivo::create(['codigo_patrimonial' => 'PC-001', 'tipo' => 'Computadora', 'estado' => $estado]);
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'tipo_mantenimiento' => 'preventivo',
            'fecha' => now()->subDays(2)->toDateString(),
            'descripcion' => 'Limpieza interna y cambio de pasta térmica.',
        ], $cambios);
    }

    public function test_el_tecnico_registra_un_mantenimiento_preventivo(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $dispositivo = $this->dispositivo();

        $this->actingAs($tecnico)->post(route('dispositivos.mantenimientos.store', $dispositivo), $this->datos())
            ->assertRedirect(route('dispositivos.show', $dispositivo))
            ->assertSessionHas('ok');

        $registro = Bitacora::where('tipo', Bitacora::TIPO_MANUAL)->firstOrFail();
        $this->assertSame('mantenimiento_preventivo', $registro->accion);
        $this->assertSame($tecnico->id, $registro->user_id);
        $this->assertSame($dispositivo->id, $registro->dispositivo_id);
        $this->assertSame(now()->subDays(2)->toDateString(), $registro->fecha->toDateString());
    }

    public function test_el_mantenimiento_correctivo_se_muestra_en_la_ficha(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $dispositivo = $this->dispositivo();

        $this->actingAs($admin)->post(route('dispositivos.mantenimientos.store', $dispositivo), $this->datos([
            'tipo_mantenimiento' => 'correctivo',
            'fecha' => now()->toDateString(),
            'descripcion' => 'Se reemplazó el disco duro dañado por un SSD.',
        ]));

        $this->actingAs($admin)->get(route('dispositivos.show', $dispositivo))
            ->assertOk()
            ->assertSee('Mantenimiento correctivo')
            ->assertSee('Se reemplazó el disco duro dañado por un SSD.');
    }

    public function test_tipo_fecha_y_descripcion_son_obligatorios(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $dispositivo = $this->dispositivo();

        $this->actingAs($tecnico)->post(route('dispositivos.mantenimientos.store', $dispositivo), [])
            ->assertSessionHasErrors(['tipo_mantenimiento', 'fecha', 'descripcion']);

        $this->assertSame(0, Bitacora::where('tipo', Bitacora::TIPO_MANUAL)->count());
    }

    public function test_no_se_acepta_un_tipo_invalido_ni_una_fecha_futura(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $dispositivo = $this->dispositivo();

        $this->actingAs($tecnico)->post(route('dispositivos.mantenimientos.store', $dispositivo), $this->datos([
            'tipo_mantenimiento' => 'predictivo',
            'fecha' => now()->addDay()->toDateString(),
        ]))->assertSessionHasErrors(['tipo_mantenimiento', 'fecha']);
    }

    public function test_no_se_registra_mantenimiento_de_un_equipo_dado_de_baja(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $dispositivo = $this->dispositivo('de_baja');

        $this->actingAs($tecnico)->post(route('dispositivos.mantenimientos.store', $dispositivo), $this->datos())
            ->assertSessionHas('error');

        $this->assertSame(0, Bitacora::where('tipo', Bitacora::TIPO_MANUAL)->count());
    }

    public function test_el_usuario_solicitante_no_registra_mantenimientos(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);
        $dispositivo = $this->dispositivo();

        $this->actingAs($docente)->post(route('dispositivos.mantenimientos.store', $dispositivo), $this->datos())
            ->assertForbidden();
    }
}
