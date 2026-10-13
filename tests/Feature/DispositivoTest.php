<?php

namespace Tests\Feature;

use App\Models\Dispositivo;
use App\Models\Role;
use App\Models\Ubicacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

/** HU03, HU04 y HU05 - Inventario de dispositivos. */
class DispositivoTest extends TestCase
{
    use RefreshDatabase, CreaUsuarios;

    private function datosValidos(array $cambios = []): array
    {
        $ubicacion = Ubicacion::firstOrCreate(['nombre' => 'Taller de Computación']);

        return array_merge([
            'codigo_patrimonial' => 'PC-001',
            'tipo' => 'Computadora',
            'marca' => 'HP',
            'modelo' => 'ProDesk 400',
            'serie' => 'SN123',
            'caracteristicas' => 'Core i5, 8 GB RAM',
            'estado' => 'operativo',
            'ubicacion_id' => $ubicacion->id,
        ], $cambios);
    }

    public function test_usuario_solicitante_no_accede_al_inventario(): void
    {
        $docente = $this->crearUsuario(Role::USUARIO);

        $this->actingAs($docente)->get(route('dispositivos.index'))->assertForbidden();
        $this->actingAs($docente)->post(route('dispositivos.store'), $this->datosValidos())->assertForbidden();
    }

    public function test_tecnico_y_administrador_acceden_al_inventario(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);

        $this->actingAs($tecnico)->get(route('dispositivos.index'))->assertOk();
        $this->actingAs($admin)->get(route('dispositivos.index'))->assertOk();
    }

    public function test_tecnico_registra_un_dispositivo_y_aparece_en_el_listado(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);

        $this->actingAs($tecnico)->post(route('dispositivos.store'), $this->datosValidos([
            'responsable_id' => $tecnico->id,
        ]))->assertRedirect(route('dispositivos.index'));

        $this->assertDatabaseHas('dispositivos', [
            'codigo_patrimonial' => 'PC-001',
            'responsable_id' => $tecnico->id,
        ]);

        $this->actingAs($tecnico)->get(route('dispositivos.index'))
            ->assertOk()
            ->assertSee('PC-001')
            ->assertSee('Taller de Computación');
    }

    public function test_no_permite_codigo_patrimonial_duplicado(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        Dispositivo::create(['codigo_patrimonial' => 'PC-001', 'tipo' => 'Computadora']);

        $this->actingAs($tecnico)->post(route('dispositivos.store'), $this->datosValidos())
            ->assertSessionHasErrors('codigo_patrimonial');

        $this->assertSame(1, Dispositivo::count());
    }

    public function test_exige_los_campos_obligatorios(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);

        $this->actingAs($tecnico)->post(route('dispositivos.store'), [])
            ->assertSessionHasErrors(['codigo_patrimonial', 'tipo', 'estado', 'ubicacion_id']);
    }

    public function test_la_ubicacion_y_el_responsable_deben_existir(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);

        $this->actingAs($tecnico)->post(route('dispositivos.store'), $this->datosValidos([
            'ubicacion_id' => 9999,
            'responsable_id' => 9999,
        ]))->assertSessionHasErrors(['ubicacion_id', 'responsable_id']);
    }

    public function test_solo_se_aceptan_los_estados_definidos(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);

        $this->actingAs($tecnico)->post(route('dispositivos.store'), $this->datosValidos(['estado' => 'roto']))
            ->assertSessionHasErrors('estado');
    }

    public function test_edita_un_dispositivo_conservando_su_propio_codigo(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $dispositivo = Dispositivo::create(['codigo_patrimonial' => 'PC-001', 'tipo' => 'Computadora']);

        $this->actingAs($tecnico)->put(route('dispositivos.update', $dispositivo), $this->datosValidos([
            'codigo_patrimonial' => 'PC-001',
            'marca' => 'Dell',
        ]))->assertRedirect(route('dispositivos.index'));

        $this->assertSame('Dell', $dispositivo->fresh()->marca);
    }

    public function test_cambia_el_estado_desde_el_listado(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $dispositivo = Dispositivo::create(['codigo_patrimonial' => 'PC-001', 'tipo' => 'Computadora']);

        $this->actingAs($tecnico)
            ->patch(route('dispositivos.estado', $dispositivo), ['estado' => 'en_mantenimiento'])
            ->assertSessionHas('ok');

        $this->assertSame('en_mantenimiento', $dispositivo->fresh()->estado);
    }

    public function test_rechaza_un_estado_invalido_en_el_cambio_rapido(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $dispositivo = Dispositivo::create(['codigo_patrimonial' => 'PC-001', 'tipo' => 'Computadora']);

        $this->actingAs($tecnico)
            ->patch(route('dispositivos.estado', $dispositivo), ['estado' => 'inventado'])
            ->assertSessionHasErrors('estado');

        $this->assertSame('operativo', $dispositivo->fresh()->estado);
    }

    public function test_el_listado_se_filtra_por_estado(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        Dispositivo::create(['codigo_patrimonial' => 'PC-OPERATIVO', 'tipo' => 'Computadora', 'estado' => 'operativo']);
        Dispositivo::create(['codigo_patrimonial' => 'PC-AVERIADO', 'tipo' => 'Computadora', 'estado' => 'inoperativo']);

        $this->actingAs($tecnico)->get(route('dispositivos.index', ['estado' => 'inoperativo']))
            ->assertOk()
            ->assertSee('PC-AVERIADO')
            ->assertDontSee('PC-OPERATIVO');
    }
}
