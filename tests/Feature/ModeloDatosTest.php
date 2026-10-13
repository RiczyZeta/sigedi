<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Dispositivo;
use App\Models\Incidencia;
use App\Models\Role;
use App\Models\Ubicacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

/** HT02 - Modelo de datos relacional: tablas, claves foráneas y restricciones. */
class ModeloDatosTest extends TestCase
{
    use RefreshDatabase, CreaUsuarios;

    public function test_las_tablas_del_modelo_existen(): void
    {
        foreach (['roles', 'users', 'ubicaciones', 'dispositivos', 'incidencias', 'bitacoras'] as $tabla) {
            $this->assertTrue(Schema::hasTable($tabla), "Falta la tabla {$tabla}");
        }
    }

    public function test_las_relaciones_entre_modelos_funcionan(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);
        $ubicacion = Ubicacion::create(['nombre' => 'Taller de Computación']);

        // Sprint 4: se desactivan los eventos para que los observers de la bitácora (HU13)
        // no agreguen registros automáticos y la prueba verifique solo las relaciones.
        [$dispositivo, $incidencia] = Model::withoutEvents(function () use ($ubicacion, $tecnico, $docente) {
            $dispositivo = Dispositivo::create([
                'codigo_patrimonial' => 'PC-001',
                'tipo' => 'Computadora',
                'ubicacion_id' => $ubicacion->id,
                'responsable_id' => $tecnico->id,
            ]);

            $incidencia = Incidencia::create([
                'codigo' => 'INC-2026-001',
                'dispositivo_id' => $dispositivo->id,
                'solicitante_id' => $docente->id,
                'categoria' => 'Hardware',
                'descripcion' => 'No enciende',
                'impacto' => 'alto',
                'urgencia' => 'alta',
                'prioridad' => 'critica',
            ]);

            return [$dispositivo, $incidencia];
        });

        Bitacora::create([
            'user_id' => $tecnico->id,
            'dispositivo_id' => $dispositivo->id,
            'incidencia_id' => $incidencia->id,
            'accion' => 'incidencia_registrada',
            'descripcion' => 'Se registró la incidencia INC-2026-001',
        ]);

        $this->assertTrue($dispositivo->ubicacion->is($ubicacion));
        $this->assertTrue($dispositivo->responsable->is($tecnico));
        $this->assertCount(1, $dispositivo->incidencias);
        $this->assertTrue($incidencia->solicitante->is($docente));
        $this->assertCount(1, $incidencia->bitacoras);
        $this->assertCount(1, $dispositivo->bitacoras);
        $this->assertSame('registrada', $incidencia->fresh()->estado);   // valor por defecto
        $this->assertSame('web', $incidencia->fresh()->canal);           // valor por defecto
    }

    public function test_no_permite_dos_dispositivos_con_el_mismo_codigo_patrimonial(): void
    {
        Dispositivo::create(['codigo_patrimonial' => 'PC-001', 'tipo' => 'Computadora']);

        $this->expectException(QueryException::class);
        Dispositivo::create(['codigo_patrimonial' => 'PC-001', 'tipo' => 'Laptop']);
    }

    public function test_las_claves_foraneas_se_respetan(): void
    {
        $this->expectException(QueryException::class);

        DB::table('dispositivos')->insert([
            'codigo_patrimonial' => 'PC-999',
            'tipo' => 'Computadora',
            'ubicacion_id' => 9999,   // ubicación inexistente
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
