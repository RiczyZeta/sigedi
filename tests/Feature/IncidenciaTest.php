<?php

namespace Tests\Feature;

use App\Models\Dispositivo;
use App\Models\Incidencia;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

/** HU09, HU10, HU11, HU12 y HU16 - Gestión de incidencias (flujo ITIL 4). */
class IncidenciaTest extends TestCase
{
    use RefreshDatabase, CreaUsuarios;

    private function crearDispositivo(string $codigo = 'PC-001', string $estado = 'operativo'): Dispositivo
    {
        return Dispositivo::create(['codigo_patrimonial' => $codigo, 'tipo' => 'Computadora', 'estado' => $estado]);
    }

    private function datosIncidencia(Dispositivo $dispositivo, array $cambios = []): array
    {
        return array_merge([
            'dispositivo_id' => $dispositivo->id,
            'categoria' => 'Hardware',
            'descripcion' => 'La computadora no enciende desde esta mañana.',
            'impacto' => 'alto',
            'urgencia' => 'alta',
        ], $cambios);
    }

    private function crearIncidencia(User $solicitante, array $atributos = []): Incidencia
    {
        $dispositivo = $atributos['dispositivo'] ?? $this->crearDispositivo('PC-'.uniqid());
        unset($atributos['dispositivo']);

        return Incidencia::create(array_merge([
            'codigo' => Incidencia::generarCodigo(),
            'dispositivo_id' => $dispositivo->id,
            'solicitante_id' => $solicitante->id,
            'categoria' => 'Hardware',
            'descripcion' => 'Falla de prueba para el flujo.',
            'impacto' => 'medio',
            'urgencia' => 'media',
            'prioridad' => 'media',
        ], $atributos));
    }

    // ---------------------------------------------------------------- HU09 · registro

    public function test_cualquier_usuario_registra_una_incidencia_con_codigo_y_estado_registrada(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);
        $dispositivo = $this->crearDispositivo();

        $respuesta = $this->actingAs($docente)->post(route('incidencias.store'), $this->datosIncidencia($dispositivo));

        $incidencia = Incidencia::firstOrFail();
        $respuesta->assertRedirect(route('incidencias.show', $incidencia));

        $this->assertSame('INC-'.now()->year.'-001', $incidencia->codigo);
        $this->assertSame('registrada', $incidencia->estado);
        $this->assertSame('web', $incidencia->canal);
        $this->assertSame($docente->id, $incidencia->solicitante_id);
        $this->assertNotNull($incidencia->created_at);   // fecha y hora del registro
    }

    public function test_los_codigos_son_consecutivos(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);
        $dispositivo = $this->crearDispositivo();

        $this->actingAs($docente)->post(route('incidencias.store'), $this->datosIncidencia($dispositivo));
        $this->actingAs($docente)->post(route('incidencias.store'), $this->datosIncidencia($dispositivo));

        $this->assertSame(
            ['INC-'.now()->year.'-001', 'INC-'.now()->year.'-002'],
            Incidencia::orderBy('id')->pluck('codigo')->all()
        );
    }

    public function test_la_numeracion_reinicia_cada_anio_y_supera_el_999(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);

        $this->assertSame('INC-2030-001', Incidencia::generarCodigo(2030));

        $this->crearIncidencia($docente, ['codigo' => 'INC-2030-999']);
        $this->assertSame('INC-2030-1000', Incidencia::generarCodigo(2030));
        $this->assertSame('INC-2031-001', Incidencia::generarCodigo(2031));
    }

    public function test_valida_los_datos_del_registro(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);

        $this->actingAs($docente)->post(route('incidencias.store'), [])
            ->assertSessionHasErrors(['dispositivo_id', 'categoria', 'descripcion', 'impacto', 'urgencia']);

        $dispositivo = $this->crearDispositivo();
        $this->actingAs($docente)->post(route('incidencias.store'), $this->datosIncidencia($dispositivo, ['descripcion' => 'corta']))
            ->assertSessionHasErrors('descripcion');

        $this->assertSame(0, Incidencia::count());
    }

    public function test_no_se_reportan_incidencias_de_equipos_dados_de_baja(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);
        $dispositivo = $this->crearDispositivo('PC-BAJA', 'de_baja');

        $this->actingAs($docente)->post(route('incidencias.store'), $this->datosIncidencia($dispositivo))
            ->assertSessionHasErrors('dispositivo_id');
    }

    // ---------------------------------------------------------------- HU10 · prioridad

    public function test_la_prioridad_se_calcula_con_la_matriz_impacto_por_urgencia(): void
    {
        $esperado = [
            'alto' => ['alta' => 'critica', 'media' => 'alta', 'baja' => 'media'],
            'medio' => ['alta' => 'alta', 'media' => 'media', 'baja' => 'baja'],
            'bajo' => ['alta' => 'media', 'media' => 'baja', 'baja' => 'baja'],
        ];

        foreach ($esperado as $impacto => $porUrgencia) {
            foreach ($porUrgencia as $urgencia => $prioridad) {
                $this->assertSame($prioridad, Incidencia::calcularPrioridad($impacto, $urgencia), "{$impacto} x {$urgencia}");
            }
        }
    }

    public function test_al_registrar_se_guarda_la_prioridad_calculada(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);
        $dispositivo = $this->crearDispositivo();

        $this->actingAs($docente)->post(route('incidencias.store'), $this->datosIncidencia($dispositivo, [
            'impacto' => 'bajo',
            'urgencia' => 'alta',
        ]));

        $this->assertSame('media', Incidencia::firstOrFail()->prioridad);
    }

    public function test_la_prioridad_tiene_un_color_distintivo(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);
        $critica = $this->crearIncidencia($docente, ['prioridad' => 'critica']);
        $baja = $this->crearIncidencia($docente, ['prioridad' => 'baja']);

        $this->assertSame('bg-danger', $critica->clasePrioridad());
        $this->assertSame('bg-success', $baja->clasePrioridad());
        $this->assertNotSame($critica->clasePrioridad(), $baja->clasePrioridad());
    }

    public function test_tecnico_reclasifica_y_la_prioridad_se_recalcula(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($docente, ['impacto' => 'alto', 'urgencia' => 'alta', 'prioridad' => 'critica']);

        $this->actingAs($tecnico)
            ->patch(route('incidencias.clasificar', $incidencia), ['impacto' => 'bajo', 'urgencia' => 'baja'])
            ->assertSessionHas('ok');

        $this->assertSame('baja', $incidencia->fresh()->prioridad);
    }

    public function test_no_se_reclasifica_una_incidencia_cerrada(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($docente, ['estado' => 'cerrada', 'solucion' => 'Listo', 'prioridad' => 'media']);

        $this->actingAs($tecnico)
            ->patch(route('incidencias.clasificar', $incidencia), ['impacto' => 'alto', 'urgencia' => 'alta'])
            ->assertSessionHas('error');

        $this->assertSame('media', $incidencia->fresh()->prioridad);
    }

    public function test_el_usuario_solicitante_no_puede_reclasificar(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($docente);

        $this->actingAs($docente)
            ->patch(route('incidencias.clasificar', $incidencia), ['impacto' => 'alto', 'urgencia' => 'alta'])
            ->assertForbidden();
    }

    // ---------------------------------------------------------------- HU11 · asignación

    public function test_solo_el_administrador_asigna_tecnicos(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($docente);

        $this->actingAs($tecnico)->patch(route('incidencias.asignar', $incidencia), ['tecnico_id' => $tecnico->id])->assertForbidden();
        $this->actingAs($docente)->patch(route('incidencias.asignar', $incidencia), ['tecnico_id' => $tecnico->id])->assertForbidden();

        $this->assertNull($incidencia->fresh()->tecnico_id);
    }

    public function test_al_asignar_el_estado_cambia_a_asignada(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($docente);

        $this->actingAs($admin)
            ->patch(route('incidencias.asignar', $incidencia), ['tecnico_id' => $tecnico->id])
            ->assertSessionHas('ok');

        $incidencia->refresh();
        $this->assertSame('asignada', $incidencia->estado);
        $this->assertSame($tecnico->id, $incidencia->tecnico_id);
        $this->assertNotNull($incidencia->fecha_asignacion);
    }

    public function test_solo_se_asigna_a_usuarios_con_rol_tecnico_activos(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $docente = $this->crearUsuario(Role::USUARIO);
        $tecnicoInactivo = $this->crearUsuario(Role::TECNICO, ['activo' => false]);
        $incidencia = $this->crearIncidencia($docente);

        $this->actingAs($admin)->patch(route('incidencias.asignar', $incidencia), ['tecnico_id' => $docente->id])
            ->assertSessionHasErrors('tecnico_id');
        $this->actingAs($admin)->patch(route('incidencias.asignar', $incidencia), ['tecnico_id' => $tecnicoInactivo->id])
            ->assertSessionHasErrors('tecnico_id');

        $this->assertSame('registrada', $incidencia->fresh()->estado);
    }

    public function test_no_se_reasigna_una_incidencia_que_ya_esta_en_atencion(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $otro = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($docente, ['estado' => 'en_atencion', 'tecnico_id' => $tecnico->id]);

        $this->actingAs($admin)
            ->patch(route('incidencias.asignar', $incidencia), ['tecnico_id' => $otro->id])
            ->assertSessionHas('error');

        $this->assertSame($tecnico->id, $incidencia->fresh()->tecnico_id);
    }

    // ---------------------------------------------------------------- HU12 · estados y cierre

    public function test_flujo_completo_de_registrada_a_cerrada(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($docente);

        $this->actingAs($admin)->patch(route('incidencias.asignar', $incidencia), ['tecnico_id' => $tecnico->id]);
        $this->assertSame('asignada', $incidencia->fresh()->estado);

        $this->actingAs($tecnico)->patch(route('incidencias.estado', $incidencia), ['estado' => 'en_atencion']);
        $this->assertSame('en_atencion', $incidencia->fresh()->estado);

        $this->actingAs($tecnico)->patch(route('incidencias.estado', $incidencia), [
            'estado' => 'resuelta',
            'solucion' => 'Se reemplazó la fuente de poder.',
        ]);
        $this->assertSame('resuelta', $incidencia->fresh()->estado);

        $this->actingAs($tecnico)->patch(route('incidencias.estado', $incidencia), ['estado' => 'cerrada']);

        $incidencia->refresh();
        $this->assertSame('cerrada', $incidencia->estado);
        $this->assertSame('Se reemplazó la fuente de poder.', $incidencia->solucion);
        $this->assertNotNull($incidencia->fecha_cierre);
    }

    public function test_no_se_permiten_transiciones_fuera_del_flujo(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($docente, ['estado' => 'asignada', 'tecnico_id' => $tecnico->id]);

        $this->actingAs($tecnico)
            ->patch(route('incidencias.estado', $incidencia), ['estado' => 'cerrada', 'solucion' => 'Intento de saltar pasos'])
            ->assertSessionHas('error');

        $this->assertSame('asignada', $incidencia->fresh()->estado);
    }

    public function test_no_se_resuelve_sin_registrar_la_solucion(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($docente, ['estado' => 'en_atencion', 'tecnico_id' => $tecnico->id]);

        $this->actingAs($tecnico)
            ->patch(route('incidencias.estado', $incidencia), ['estado' => 'resuelta', 'solucion' => ''])
            ->assertSessionHasErrors('solucion');

        $this->assertSame('en_atencion', $incidencia->fresh()->estado);
    }

    public function test_no_se_cierra_sin_registrar_la_solucion(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($docente, ['estado' => 'resuelta', 'tecnico_id' => $tecnico->id, 'solucion' => null]);

        $this->actingAs($tecnico)
            ->patch(route('incidencias.estado', $incidencia), ['estado' => 'cerrada'])
            ->assertSessionHasErrors('solucion');

        $this->assertSame('resuelta', $incidencia->fresh()->estado);
        $this->assertNull($incidencia->fresh()->fecha_cierre);
    }

    public function test_una_incidencia_resuelta_puede_reabrirse(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($docente, [
            'estado' => 'resuelta', 'tecnico_id' => $tecnico->id, 'solucion' => 'Reinicio del equipo',
        ]);

        $this->actingAs($tecnico)->patch(route('incidencias.estado', $incidencia), ['estado' => 'en_atencion']);

        $this->assertSame('en_atencion', $incidencia->fresh()->estado);
    }

    public function test_un_tecnico_no_asignado_no_puede_cambiar_el_estado(): void
    {
        $asignado = $this->crearUsuario(Role::TECNICO);
        $otro = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($docente, ['estado' => 'asignada', 'tecnico_id' => $asignado->id]);

        $this->actingAs($otro)
            ->patch(route('incidencias.estado', $incidencia), ['estado' => 'en_atencion'])
            ->assertForbidden();

        $this->assertSame('asignada', $incidencia->fresh()->estado);
    }

    public function test_el_usuario_solicitante_no_puede_cambiar_el_estado(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($docente, ['estado' => 'asignada']);

        $this->actingAs($docente)
            ->patch(route('incidencias.estado', $incidencia), ['estado' => 'en_atencion'])
            ->assertForbidden();
    }

    // ---------------------------------------------------------------- HU16 · consulta del solicitante

    public function test_el_solicitante_solo_ve_sus_propias_incidencias(): void
    {
        $ana = $this->crearUsuario(Role::USUARIO);
        $luis = $this->crearUsuario(Role::USUARIO);
        $deAna = $this->crearIncidencia($ana, ['codigo' => 'INC-2026-101']);
        $deLuis = $this->crearIncidencia($luis, ['codigo' => 'INC-2026-202']);

        $this->actingAs($ana)->get(route('incidencias.index'))
            ->assertOk()
            ->assertSee('INC-2026-101')
            ->assertDontSee('INC-2026-202');

        $this->actingAs($ana)->get(route('incidencias.show', $deAna))->assertOk()->assertSee('INC-2026-101');
        $this->actingAs($ana)->get(route('incidencias.show', $deLuis))->assertForbidden();
    }

    public function test_el_solicitante_ve_el_estado_actual_de_su_incidencia(): void
    {
        $ana = $this->crearUsuario(Role::USUARIO);
        $incidencia = $this->crearIncidencia($ana, ['estado' => 'en_atencion']);

        $this->actingAs($ana)->get(route('incidencias.show', $incidencia))
            ->assertOk()
            ->assertSee('En atención');
    }

    public function test_tecnico_y_administrador_ven_todas_las_incidencias(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $ana = $this->crearUsuario(Role::USUARIO);
        $luis = $this->crearUsuario(Role::USUARIO);
        $this->crearIncidencia($ana, ['codigo' => 'INC-2026-101']);
        $this->crearIncidencia($luis, ['codigo' => 'INC-2026-202']);

        foreach ([$tecnico, $admin] as $usuario) {
            $this->actingAs($usuario)->get(route('incidencias.index'))
                ->assertOk()
                ->assertSee('INC-2026-101')
                ->assertSee('INC-2026-202');
        }
    }

    public function test_el_listado_se_filtra_por_estado(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $ana = $this->crearUsuario(Role::USUARIO);
        $this->crearIncidencia($ana, ['codigo' => 'INC-2026-101', 'estado' => 'registrada']);
        $this->crearIncidencia($ana, ['codigo' => 'INC-2026-202', 'estado' => 'cerrada', 'solucion' => 'Listo']);

        $this->actingAs($tecnico)->get(route('incidencias.index', ['estado' => 'cerrada']))
            ->assertOk()
            ->assertSee('INC-2026-202')
            ->assertDontSee('INC-2026-101');
    }

    public function test_el_listado_ordena_por_prioridad(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $ana = $this->crearUsuario(Role::USUARIO);
        $this->crearIncidencia($ana, ['codigo' => 'INC-2026-101', 'prioridad' => 'baja']);
        $this->crearIncidencia($ana, ['codigo' => 'INC-2026-202', 'prioridad' => 'critica']);

        $this->actingAs($tecnico)->get(route('incidencias.index'))
            ->assertOk()
            ->assertSeeInOrder(['INC-2026-202', 'INC-2026-101']);
    }

    public function test_un_visitante_es_redirigido_al_login(): void
    {
        $this->get(route('incidencias.index'))->assertRedirect(route('login'));
        $this->get(route('incidencias.create'))->assertRedirect(route('login'));
    }

    public function test_el_formulario_de_registro_lista_los_dispositivos_activos(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);
        $this->crearDispositivo('PC-ACTIVO');
        $this->crearDispositivo('PC-DE-BAJA', 'de_baja');

        $this->actingAs($docente)->get(route('incidencias.create'))
            ->assertOk()
            ->assertSee('PC-ACTIVO')
            ->assertDontSee('PC-DE-BAJA');
    }
}
