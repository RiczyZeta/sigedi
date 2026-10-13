<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Dispositivo;
use App\Models\Incidencia;
use App\Models\Role;
use App\Models\Ubicacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

/** HU15 - Ficha del dispositivo con su historial de incidencias y actividades (Sprint 4). */
class FichaDispositivoTest extends TestCase
{
    use RefreshDatabase, CreaUsuarios;

    private function incidencia(Dispositivo $dispositivo, string $codigo, string $creada): Incidencia
    {
        $incidencia = Incidencia::create([
            'codigo' => $codigo,
            'dispositivo_id' => $dispositivo->id,
            'solicitante_id' => $this->crearUsuario(Role::USUARIO)->id,
            'categoria' => 'Hardware',
            'descripcion' => 'Falla de prueba.',
            'impacto' => 'medio',
            'urgencia' => 'media',
            'prioridad' => 'media',
        ]);
        $incidencia->forceFill(['created_at' => $creada])->saveQuietly();

        return $incidencia;
    }

    public function test_la_ficha_muestra_los_datos_generales(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO, ['name' => 'Carlos Responsable']);
        $ubicacion = Ubicacion::create(['nombre' => 'Laboratorio 1']);
        $dispositivo = Dispositivo::create([
            'codigo_patrimonial' => 'PC-LAB1-05',
            'tipo' => 'Computadora',
            'marca' => 'Lenovo',
            'modelo' => 'ThinkCentre M70',
            'serie' => 'SN-7788',
            'caracteristicas' => 'Core i5, 16 GB RAM',
            'estado' => 'operativo',
            'ubicacion_id' => $ubicacion->id,
            'responsable_id' => $tecnico->id,
        ]);

        $this->actingAs($tecnico)->get(route('dispositivos.show', $dispositivo))
            ->assertOk()
            ->assertSee('PC-LAB1-05')
            ->assertSee('Lenovo ThinkCentre M70')
            ->assertSee('SN-7788')
            ->assertSee('Laboratorio 1')
            ->assertSee('Carlos Responsable')
            ->assertSee('Operativo');
    }

    public function test_la_ficha_lista_las_incidencias_de_la_mas_reciente_a_la_mas_antigua(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $dispositivo = Dispositivo::create(['codigo_patrimonial' => 'PC-001', 'tipo' => 'Computadora']);
        $otro = Dispositivo::create(['codigo_patrimonial' => 'PC-002', 'tipo' => 'Computadora']);
        $this->incidencia($dispositivo, 'INC-2026-001', '2026-09-01 08:00:00');
        $this->incidencia($dispositivo, 'INC-2026-005', '2026-10-01 08:00:00');
        $this->incidencia($otro, 'INC-2026-009', '2026-10-02 08:00:00');

        $this->actingAs($admin)->get(route('dispositivos.show', $dispositivo))
            ->assertOk()
            ->assertSeeInOrder(['INC-2026-005', 'INC-2026-001'])
            ->assertDontSee('INC-2026-009');
    }

    public function test_la_ficha_muestra_la_bitacora_ordenada_por_fecha_descendente(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $dispositivo = Dispositivo::create(['codigo_patrimonial' => 'PC-001', 'tipo' => 'Computadora']);

        Bitacora::create([
            'dispositivo_id' => $dispositivo->id, 'tipo' => Bitacora::TIPO_MANUAL, 'accion' => 'mantenimiento_preventivo',
            'descripcion' => 'Mantenimiento ANTIGUO', 'fecha' => now()->subMonth(),
        ]);
        Bitacora::create([
            'dispositivo_id' => $dispositivo->id, 'tipo' => Bitacora::TIPO_MANUAL, 'accion' => 'mantenimiento_correctivo',
            'descripcion' => 'Mantenimiento RECIENTE', 'fecha' => now()->addMinute(),
        ]);

        $this->actingAs($tecnico)->get(route('dispositivos.show', $dispositivo))
            ->assertOk()
            ->assertSeeInOrder(['Mantenimiento RECIENTE', 'Se registró el dispositivo PC-001', 'Mantenimiento ANTIGUO']);
    }

    public function test_la_ficha_incluye_las_acciones_de_las_incidencias_del_dispositivo(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $dispositivo = Dispositivo::create(['codigo_patrimonial' => 'PC-001', 'tipo' => 'Computadora']);
        $this->incidencia($dispositivo, 'INC-2026-001', now()->toDateTimeString());

        $this->actingAs($admin)->get(route('dispositivos.show', $dispositivo))
            ->assertOk()
            ->assertSee('Registro de incidencia')
            ->assertSee('Se registró la incidencia INC-2026-001');
    }

    public function test_el_usuario_solicitante_no_accede_a_la_ficha(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);
        $dispositivo = Dispositivo::create(['codigo_patrimonial' => 'PC-001', 'tipo' => 'Computadora']);

        $this->actingAs($docente)->get(route('dispositivos.show', $dispositivo))->assertForbidden();
    }
}
