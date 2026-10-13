<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Dispositivo;
use App\Models\Incidencia;
use App\Models\Role;
use App\Models\Ubicacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

/** HU13 - Bitácora automática de acciones sobre dispositivos e incidencias (Sprint 4). */
class BitacoraTest extends TestCase
{
    use RefreshDatabase, CreaUsuarios;

    private function datosDispositivo(array $cambios = []): array
    {
        return array_merge([
            'codigo_patrimonial' => 'PC-001',
            'tipo' => 'Computadora',
            'estado' => 'operativo',
            'ubicacion_id' => Ubicacion::firstOrCreate(['nombre' => 'Taller de Computación'])->id,
        ], $cambios);
    }

    private function ultimo(string $accion): ?Bitacora
    {
        return Bitacora::where('accion', $accion)->latest('id')->first();
    }

    // ---------------------------------------------------------------- Dispositivos

    public function test_registrar_un_dispositivo_genera_un_registro_con_usuario_fecha_y_descripcion(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);

        $this->actingAs($tecnico)->post(route('dispositivos.store'), $this->datosDispositivo());

        $registro = $this->ultimo('registro_dispositivo');
        $this->assertNotNull($registro);
        $this->assertSame($tecnico->id, $registro->user_id);
        $this->assertSame(Dispositivo::first()->id, $registro->dispositivo_id);
        $this->assertSame(Bitacora::TIPO_AUTOMATICA, $registro->tipo);
        $this->assertNotNull($registro->fecha);
        $this->assertStringContainsString('PC-001', $registro->descripcion);
    }

    public function test_el_cambio_de_estado_del_dispositivo_queda_registrado(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $dispositivo = Dispositivo::create($this->datosDispositivo());

        $this->actingAs($tecnico)->patch(route('dispositivos.estado', $dispositivo), ['estado' => 'en_mantenimiento']);

        $registro = $this->ultimo('cambio_estado_dispositivo');
        $this->assertNotNull($registro);
        $this->assertSame($tecnico->id, $registro->user_id);
        $this->assertStringContainsString('«Operativo» → «En mantenimiento»', $registro->descripcion);
    }

    public function test_el_cambio_de_ubicacion_y_de_responsable_queda_registrado(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $taller = Ubicacion::create(['nombre' => 'Taller de Computación']);
        $direccion = Ubicacion::create(['nombre' => 'Dirección']);
        $dispositivo = Dispositivo::create($this->datosDispositivo(['ubicacion_id' => $taller->id]));

        $this->actingAs($admin)->put(route('dispositivos.update', $dispositivo), $this->datosDispositivo([
            'ubicacion_id' => $direccion->id,
            'responsable_id' => $admin->id,
        ]));

        $this->assertStringContainsString('«Taller de Computación» → «Dirección»', $this->ultimo('cambio_ubicacion')->descripcion);
        $this->assertStringContainsString($admin->name, $this->ultimo('cambio_responsable')->descripcion);
    }

    public function test_guardar_sin_cambios_no_genera_registros(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $dispositivo = Dispositivo::create($this->datosDispositivo());
        $antes = Bitacora::count();

        $this->actingAs($admin)->put(route('dispositivos.update', $dispositivo), $this->datosDispositivo());

        $this->assertSame($antes, Bitacora::count());
    }

    // ---------------------------------------------------------------- Incidencias

    public function test_el_ciclo_de_vida_de_la_incidencia_queda_registrado_en_orden(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $dispositivo = Dispositivo::create($this->datosDispositivo());

        $this->actingAs($docente)->post(route('incidencias.store'), [
            'dispositivo_id' => $dispositivo->id,
            'categoria' => 'Hardware',
            'descripcion' => 'La computadora no enciende.',
            'impacto' => 'alto',
            'urgencia' => 'alta',
        ]);
        $incidencia = Incidencia::firstOrFail();

        $this->actingAs($admin)->patch(route('incidencias.asignar', $incidencia), ['tecnico_id' => $tecnico->id]);
        $this->actingAs($tecnico)->patch(route('incidencias.estado', $incidencia), ['estado' => 'en_atencion']);
        $this->actingAs($tecnico)->patch(route('incidencias.estado', $incidencia), ['estado' => 'resuelta', 'solucion' => 'Se cambió la fuente de poder.']);
        $this->actingAs($tecnico)->patch(route('incidencias.estado', $incidencia), ['estado' => 'cerrada']);

        $registros = $incidencia->bitacoras()->orderBy('id')->get();

        $this->assertSame(
            ['registro_incidencia', 'asignacion', 'cambio_estado_incidencia', 'resolucion', 'cierre'],
            $registros->pluck('accion')->all()
        );
        $this->assertSame(
            [$docente->id, $admin->id, $tecnico->id, $tecnico->id, $tecnico->id],
            $registros->pluck('user_id')->all()
        );
        // Cada acción de la incidencia también queda en la bitácora de su dispositivo (HU15).
        $this->assertTrue($registros->every(fn ($r) => $r->dispositivo_id === $dispositivo->id));
        $this->assertStringContainsString('Se cambió la fuente de poder.', $registros->last()->descripcion);
    }

    public function test_la_reapertura_y_la_reclasificacion_quedan_registradas(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $dispositivo = Dispositivo::create($this->datosDispositivo());
        $incidencia = Incidencia::create([
            'codigo' => 'INC-2026-001',
            'dispositivo_id' => $dispositivo->id,
            'solicitante_id' => $docente->id,
            'tecnico_id' => $tecnico->id,
            'categoria' => 'Software',
            'descripcion' => 'No abre el navegador.',
            'impacto' => 'bajo',
            'urgencia' => 'baja',
            'prioridad' => 'baja',
            'estado' => 'resuelta',
            'solucion' => 'Se reinstaló el navegador.',
        ]);

        $this->actingAs($tecnico)->patch(route('incidencias.estado', $incidencia), ['estado' => 'en_atencion']);
        $this->actingAs($tecnico)->patch(route('incidencias.clasificar', $incidencia), ['impacto' => 'alto', 'urgencia' => 'alta']);

        $this->assertNotNull($this->ultimo('reapertura'));
        $this->assertStringContainsString('«Baja» → «Crítica»', $this->ultimo('reclasificacion')->descripcion);
    }

    public function test_la_reasignacion_indica_el_tecnico_anterior_y_el_nuevo(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $docente = $this->crearUsuario(Role::USUARIO);
        $pedro = $this->crearUsuario(Role::TECNICO, ['name' => 'Pedro Técnico']);
        $rosa = $this->crearUsuario(Role::TECNICO, ['name' => 'Rosa Técnica']);
        $incidencia = Incidencia::create([
            'codigo' => 'INC-2026-001',
            'dispositivo_id' => Dispositivo::create($this->datosDispositivo())->id,
            'solicitante_id' => $docente->id,
            'categoria' => 'Hardware',
            'descripcion' => 'Falla de prueba.',
            'impacto' => 'medio',
            'urgencia' => 'media',
            'prioridad' => 'media',
        ]);

        $this->actingAs($admin)->patch(route('incidencias.asignar', $incidencia), ['tecnico_id' => $pedro->id]);
        $this->actingAs($admin)->patch(route('incidencias.asignar', $incidencia), ['tecnico_id' => $rosa->id]);

        $this->assertStringContainsString('de Pedro Técnico a Rosa Técnica', $this->ultimo('asignacion')->descripcion);
        $this->assertSame(2, Bitacora::where('accion', 'asignacion')->count());
    }

    // ---------------------------------------------------------------- Inmutabilidad y acceso

    public function test_los_registros_no_se_pueden_editar(): void
    {
        Dispositivo::create($this->datosDispositivo());
        $registro = Bitacora::firstOrFail();

        $this->expectException(LogicException::class);
        $registro->update(['descripcion' => 'Texto alterado']);
    }

    public function test_los_registros_no_se_pueden_eliminar(): void
    {
        Dispositivo::create($this->datosDispositivo());
        $registro = Bitacora::firstOrFail();

        try {
            $registro->delete();
            $this->fail('Se esperaba una excepción al eliminar un registro de la bitácora.');
        } catch (LogicException) {
            $this->assertDatabaseHas('bitacoras', ['id' => $registro->id]);
        }
    }

    public function test_la_bitacora_se_consulta_y_filtra_y_el_solicitante_no_accede(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        Dispositivo::create($this->datosDispositivo(['codigo_patrimonial' => 'PC-AAA']));
        Dispositivo::create($this->datosDispositivo(['codigo_patrimonial' => 'IMP-BBB', 'tipo' => 'Impresora']));

        $this->actingAs($tecnico)->get(route('bitacora.index'))
            ->assertOk()->assertSee('PC-AAA')->assertSee('IMP-BBB');

        $this->actingAs($tecnico)->get(route('bitacora.index', ['q' => 'IMP-B']))
            ->assertOk()->assertSee('IMP-BBB')->assertDontSee('PC-AAA');

        $this->actingAs($tecnico)->get(route('bitacora.index', ['tipo' => 'manual']))
            ->assertOk()->assertSee('No hay actividades registradas.');

        $this->actingAs($docente)->get(route('bitacora.index'))->assertForbidden();
    }

    public function test_no_existen_rutas_para_editar_ni_eliminar_la_bitacora(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        Dispositivo::create($this->datosDispositivo());
        $registro = Bitacora::firstOrFail();

        $this->actingAs($admin)->delete('/bitacora/'.$registro->id)->assertNotFound();
        $this->actingAs($admin)->put('/bitacora/'.$registro->id, ['descripcion' => 'x'])->assertNotFound();
        $this->assertDatabaseHas('bitacoras', ['id' => $registro->id, 'descripcion' => $registro->descripcion]);
    }
}
