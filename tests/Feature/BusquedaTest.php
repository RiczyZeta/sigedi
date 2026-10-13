<?php

namespace Tests\Feature;

use App\Models\Dispositivo;
use App\Models\Incidencia;
use App\Models\Role;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

/** HU07 - Búsqueda y consulta de dispositivos e incidencias (Sprint 4). */
class BusquedaTest extends TestCase
{
    use RefreshDatabase, CreaUsuarios;

    private function dispositivo(string $codigo, array $atributos = []): Dispositivo
    {
        return Dispositivo::create(array_merge([
            'codigo_patrimonial' => $codigo,
            'tipo' => 'Computadora',
            'estado' => 'operativo',
        ], $atributos));
    }

    private function incidencia(User $solicitante, Dispositivo $dispositivo, string $codigo, array $atributos = []): Incidencia
    {
        return Incidencia::create(array_merge([
            'codigo' => $codigo,
            'dispositivo_id' => $dispositivo->id,
            'solicitante_id' => $solicitante->id,
            'categoria' => 'Hardware',
            'descripcion' => 'Falla de prueba.',
            'impacto' => 'medio',
            'urgencia' => 'media',
            'prioridad' => 'media',
        ], $atributos));
    }

    // ---------------------------------------------------------------- Dispositivos

    public function test_la_busqueda_acepta_coincidencias_parciales_del_codigo(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $this->dispositivo('LAB1-PC-001');
        $this->dispositivo('LAB1-PC-002');
        $this->dispositivo('IMP-010', ['tipo' => 'Impresora']);

        $this->actingAs($tecnico)->get(route('dispositivos.index', ['q' => 'pc-00']))
            ->assertOk()
            ->assertSee('LAB1-PC-001')
            ->assertSee('LAB1-PC-002')
            ->assertDontSee('IMP-010');
    }

    public function test_se_busca_por_tipo(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $this->dispositivo('PC-001');
        $this->dispositivo('PROY-001', ['tipo' => 'Proyector']);

        $this->actingAs($tecnico)->get(route('dispositivos.index', ['tipo' => 'Proyector']))
            ->assertOk()
            ->assertSee('PROY-001')
            ->assertDontSee('PC-001');
    }

    public function test_se_busca_por_ubicacion(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $taller = Ubicacion::create(['nombre' => 'Taller de Computación']);
        $direccion = Ubicacion::create(['nombre' => 'Dirección']);
        $this->dispositivo('PC-TALLER', ['ubicacion_id' => $taller->id]);
        $this->dispositivo('PC-DIRECCION', ['ubicacion_id' => $direccion->id]);

        $this->actingAs($tecnico)->get(route('dispositivos.index', ['ubicacion_id' => $direccion->id]))
            ->assertOk()
            ->assertSee('PC-DIRECCION')
            ->assertDontSee('PC-TALLER');
    }

    public function test_los_filtros_se_combinan(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $taller = Ubicacion::create(['nombre' => 'Taller de Computación']);
        $this->dispositivo('PC-A1', ['ubicacion_id' => $taller->id, 'estado' => 'inoperativo']);
        $this->dispositivo('PC-A2', ['ubicacion_id' => $taller->id, 'estado' => 'operativo']);
        $this->dispositivo('PC-A3', ['estado' => 'inoperativo']);
        $this->dispositivo('LAP-A4', ['ubicacion_id' => $taller->id, 'estado' => 'inoperativo', 'tipo' => 'Laptop']);

        $this->actingAs($tecnico)->get(route('dispositivos.index', [
            'q' => 'PC-A',
            'tipo' => 'Computadora',
            'ubicacion_id' => $taller->id,
            'estado' => 'inoperativo',
        ]))
            ->assertOk()
            ->assertSee('PC-A1')
            ->assertDontSee('PC-A2')
            ->assertDontSee('PC-A3')
            ->assertDontSee('LAP-A4')
            ->assertSee('1 dispositivo encontrado');
    }

    public function test_los_resultados_se_paginan_y_conservan_los_filtros(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        foreach (range(1, 17) as $n) {
            $this->dispositivo(sprintf('PC-%03d', $n));
        }

        $this->actingAs($tecnico)->get(route('dispositivos.index', ['q' => 'PC']))
            ->assertOk()
            ->assertSee('PC-015')
            ->assertDontSee('PC-016')
            ->assertSee('q=PC&amp;page=2', false);   // el enlace a la página 2 conserva el filtro

        $this->actingAs($tecnico)->get(route('dispositivos.index', ['q' => 'PC', 'page' => 2]))
            ->assertOk()
            ->assertSee('PC-016')
            ->assertSee('PC-017');
    }

    public function test_los_comodines_de_like_se_buscan_como_texto(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $this->dispositivo('PC-100');

        $this->actingAs($tecnico)->get(route('dispositivos.index', ['q' => '%']))
            ->assertOk()
            ->assertDontSee('PC-100')
            ->assertSee('No se encontraron dispositivos');
    }

    // ---------------------------------------------------------------- Incidencias

    public function test_se_buscan_incidencias_por_codigo_parcial_o_por_dispositivo(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $docente = $this->crearUsuario(Role::USUARIO);
        $pc = $this->dispositivo('PC-LAB-07');
        $impresora = $this->dispositivo('IMP-DIR-01', ['tipo' => 'Impresora']);
        $this->incidencia($docente, $pc, 'INC-2026-011');
        $this->incidencia($docente, $impresora, 'INC-2026-020');

        $this->actingAs($admin)->get(route('incidencias.index', ['q' => '2026-01']))
            ->assertOk()->assertSee('INC-2026-011')->assertDontSee('INC-2026-020');

        $this->actingAs($admin)->get(route('incidencias.index', ['q' => 'IMP-DIR']))
            ->assertOk()->assertSee('INC-2026-020')->assertDontSee('INC-2026-011');
    }

    public function test_se_filtran_incidencias_por_prioridad_y_categoria_combinadas(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $pc = $this->dispositivo('PC-001');
        $this->incidencia($docente, $pc, 'INC-2026-101', ['prioridad' => 'critica', 'categoria' => 'Red e Internet']);
        $this->incidencia($docente, $pc, 'INC-2026-102', ['prioridad' => 'critica', 'categoria' => 'Hardware']);
        $this->incidencia($docente, $pc, 'INC-2026-103', ['prioridad' => 'baja', 'categoria' => 'Red e Internet']);

        $this->actingAs($tecnico)->get(route('incidencias.index', ['prioridad' => 'critica', 'categoria' => 'Red e Internet']))
            ->assertOk()
            ->assertSee('INC-2026-101')
            ->assertDontSee('INC-2026-102')
            ->assertDontSee('INC-2026-103');
    }

    public function test_el_solicitante_solo_encuentra_sus_incidencias_aunque_busque_otras(): void
    {
        $ana = $this->crearUsuario(Role::USUARIO);
        $luis = $this->crearUsuario(Role::USUARIO);
        $pc = $this->dispositivo('PC-001');
        $this->incidencia($ana, $pc, 'INC-2026-201');
        $deLuis = $this->incidencia($luis, $pc, 'INC-2026-202');

        $this->actingAs($ana)->get(route('incidencias.index', ['q' => 'INC-2026-202']))
            ->assertOk()
            ->assertDontSee(route('incidencias.show', $deLuis))
            ->assertSee('0 incidencias encontradas');

        $this->actingAs($ana)->get(route('incidencias.index', ['q' => 'PC-001']))
            ->assertOk()
            ->assertSee('INC-2026-201')
            ->assertSee('1 incidencia encontrada');
    }

    public function test_el_usuario_solicitante_no_accede_a_la_busqueda_de_dispositivos(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);

        $this->actingAs($docente)->get(route('dispositivos.index', ['q' => 'PC']))->assertForbidden();
    }
}
