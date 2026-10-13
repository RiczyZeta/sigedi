<?php

namespace Tests\Feature;

use App\Models\Dispositivo;
use App\Models\Role;
use App\Models\Ubicacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

/** HU06 - Importar el inventario desde Excel. */
class ImportacionDispositivosTest extends TestCase
{
    use RefreshDatabase, CreaUsuarios;

    private const ENCABEZADO = [
        'codigo_patrimonial', 'tipo', 'marca', 'modelo', 'serie',
        'caracteristicas', 'estado', 'ubicacion', 'responsable_correo',
    ];

    /** Genera un .xlsx temporal con las filas indicadas y devuelve el archivo para subirlo. */
    private function crearExcel(array $filas): UploadedFile
    {
        $libro = new Spreadsheet();
        $libro->getActiveSheet()->fromArray($filas, null, 'A1');

        $ruta = tempnam(sys_get_temp_dir(), 'inv').'.xlsx';
        (new Xlsx($libro))->save($ruta);

        return new UploadedFile($ruta, 'inventario.xlsx', null, null, true);
    }

    public function test_solo_el_administrador_puede_importar(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO);
        $docente = $this->crearUsuario(Role::USUARIO);

        $this->actingAs($tecnico)->get(route('dispositivos.importar'))->assertForbidden();
        $this->actingAs($docente)->get(route('dispositivos.plantilla'))->assertForbidden();
    }

    public function test_el_administrador_ve_el_formulario_y_descarga_la_plantilla(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);

        $this->actingAs($admin)->get(route('dispositivos.importar'))->assertOk()->assertSee('Descargar plantilla');
        $respuesta = $this->actingAs($admin)->get(route('dispositivos.plantilla'));
        $respuesta->assertOk()->assertDownload('plantilla_dispositivos.xlsx');

        // Un .xlsx es un archivo ZIP: su contenido empieza con "PK"
        $this->assertStringStartsWith('PK', $respuesta->streamedContent());
    }

    public function test_importa_las_filas_validas_e_informa_las_que_tienen_errores(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $this->crearUsuario(Role::TECNICO, ['email' => 'resp@ina54.test']);
        Ubicacion::create(['nombre' => 'Taller de Computación']);

        $archivo = $this->crearExcel([
            self::ENCABEZADO,
            // fila 2: válida
            ['PC-001', 'Computadora', 'HP', 'ProDesk', 'SN1', 'Core i5', 'operativo', 'Taller de Computación', null],
            // fila 3: válida, estado con espacios y responsable existente
            ['PC-002', 'Laptop', 'Lenovo', null, null, null, 'En mantenimiento', 'taller de computación', 'resp@ina54.test'],
            // fila 4: código repetido dentro del archivo
            ['PC-001', 'Computadora', null, null, null, null, null, null, null],
            // fila 5: falta el código
            [null, 'Impresora', null, null, null, null, null, null, null],
            // fila 6: ubicación inexistente
            ['PC-005', 'Proyector', null, null, null, null, null, 'Sala que no existe', null],
        ]);

        $this->actingAs($admin)
            ->post(route('dispositivos.importar.store'), ['archivo' => $archivo])
            ->assertSessionHas('resultado', function (array $r) {
                return $r['total'] === 5
                    && $r['creados'] === 2
                    && array_column($r['errores'], 'fila') === [4, 5, 6];
            });

        $this->assertSame(2, Dispositivo::count());
        $this->assertDatabaseHas('dispositivos', ['codigo_patrimonial' => 'PC-001', 'estado' => 'operativo']);
        $this->assertDatabaseHas('dispositivos', ['codigo_patrimonial' => 'PC-002', 'estado' => 'en_mantenimiento']);
        $this->assertDatabaseMissing('dispositivos', ['codigo_patrimonial' => 'PC-005']);
    }

    public function test_no_importa_un_codigo_que_ya_existe_en_el_sistema(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        Dispositivo::create(['codigo_patrimonial' => 'PC-001', 'tipo' => 'Computadora']);

        $archivo = $this->crearExcel([
            self::ENCABEZADO,
            ['pc-001', 'Computadora', null, null, null, null, null, null, null],
        ]);

        $this->actingAs($admin)
            ->post(route('dispositivos.importar.store'), ['archivo' => $archivo])
            ->assertSessionHas('resultado', fn (array $r) => $r['creados'] === 0 && count($r['errores']) === 1);

        $this->assertSame(1, Dispositivo::count());
    }

    public function test_rechaza_un_archivo_sin_las_columnas_obligatorias(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);

        $archivo = $this->crearExcel([
            ['nombre', 'cantidad'],
            ['Mouse', 5],
        ]);

        $this->actingAs($admin)
            ->post(route('dispositivos.importar.store'), ['archivo' => $archivo])
            ->assertSessionHas('error');

        $this->assertSame(0, Dispositivo::count());
    }

    public function test_rechaza_un_archivo_que_no_es_xlsx(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);

        $this->actingAs($admin)
            ->post(route('dispositivos.importar.store'), [
                'archivo' => UploadedFile::fake()->create('inventario.txt', 1, 'text/plain'),
            ])
            ->assertSessionHasErrors('archivo');
    }

    public function test_exige_seleccionar_un_archivo(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);

        $this->actingAs($admin)
            ->post(route('dispositivos.importar.store'), [])
            ->assertSessionHasErrors('archivo');
    }
}
