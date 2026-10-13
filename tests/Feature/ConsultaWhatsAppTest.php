<?php

namespace Tests\Feature;

use App\Models\Dispositivo;
use App\Models\Incidencia;
use App\Models\Role;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreaUsuarios;
use Tests\Concerns\SimulaWhatsApp;
use Tests\TestCase;

/** HU18 - Consultas en lenguaje natural por WhatsApp (Sprint 5). */
class ConsultaWhatsAppTest extends TestCase
{
    use RefreshDatabase, CreaUsuarios, SimulaWhatsApp;

    private const NUMERO = '51987654321';

    private User $docente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurarWhatsApp();
        $this->fingirApis();
        $this->docente = $this->crearUsuario(Role::USUARIO, ['name' => 'Ana Torres', 'whatsapp' => '+'.self::NUMERO]);
    }

    private function dispositivo(string $codigo = 'PC-LAB1-05', string $estado = 'en_mantenimiento'): Dispositivo
    {
        return Dispositivo::create([
            'codigo_patrimonial' => $codigo,
            'tipo' => 'Computadora',
            'marca' => 'HP',
            'modelo' => 'ProDesk 400',
            'estado' => $estado,
            'ubicacion_id' => Ubicacion::firstOrCreate(['nombre' => 'Laboratorio 1'])->id,
        ]);
    }

    private function incidencia(User $solicitante, string $codigo, array $atributos = []): Incidencia
    {
        return Incidencia::create(array_merge([
            'codigo' => $codigo,
            'dispositivo_id' => $this->dispositivo('PC-'.uniqid())->id,
            'solicitante_id' => $solicitante->id,
            'categoria' => 'Hardware',
            'descripcion' => 'No enciende.',
            'impacto' => 'medio',
            'urgencia' => 'media',
            'prioridad' => 'media',
        ], $atributos));
    }

    public function test_responde_el_estado_real_de_un_dispositivo(): void
    {
        $this->dispositivo();

        $this->escribir(self::NUMERO, '¿Cuál es el estado de la pc-lab1-05?')->assertOk();

        $respuesta = $this->ultimaRespuesta();
        $this->assertStringContainsString('PC-LAB1-05', $respuesta);
        $this->assertStringContainsString('En mantenimiento', $respuesta);
        $this->assertStringContainsString('Laboratorio 1', $respuesta);
    }

    public function test_si_el_dispositivo_no_existe_lo_indica_sin_inventar(): void
    {
        $this->dispositivo();

        $this->escribir(self::NUMERO, 'estado de PC-LAB9-99')->assertOk();

        $respuesta = $this->ultimaRespuesta();
        $this->assertStringContainsString('No encontré un dispositivo con el código *PC-LAB9-99*', $respuesta);
        $this->assertStringNotContainsString('Estado:', $respuesta);
    }

    public function test_el_solicitante_consulta_su_incidencia(): void
    {
        $this->incidencia($this->docente, 'INC-2026-015', ['estado' => 'en_atencion']);

        $this->escribir(self::NUMERO, 'como va mi ticket inc-2026-015?')->assertOk();

        $respuesta = $this->ultimaRespuesta();
        $this->assertStringContainsString('INC-2026-015', $respuesta);
        $this->assertStringContainsString('En atención', $respuesta);
    }

    public function test_el_solicitante_no_consulta_incidencias_ajenas(): void
    {
        $otro = $this->crearUsuario(Role::USUARIO);
        $this->incidencia($otro, 'INC-2026-020');

        $this->escribir(self::NUMERO, 'INC-2026-020')->assertOk();

        $this->assertSame('Solo puedes consultar las incidencias que registraste.', $this->ultimaRespuesta());
    }

    public function test_el_tecnico_consulta_cualquier_incidencia(): void
    {
        $tecnico = $this->crearUsuario(Role::TECNICO, ['whatsapp' => '51911111111']);
        $this->incidencia($this->docente, 'INC-2026-030');

        $this->escribir('51911111111', 'INC-2026-030')->assertOk();

        $this->assertStringContainsString('Estado: *Registrada*', $this->ultimaRespuesta('51911111111'));
    }

    public function test_una_incidencia_inexistente_se_informa(): void
    {
        $this->escribir(self::NUMERO, 'INC-2026-999')->assertOk();

        $this->assertStringContainsString('No encontré la incidencia *INC-2026-999*', $this->ultimaRespuesta());
    }

    public function test_mis_incidencias_lista_solo_las_abiertas_del_usuario(): void
    {
        $this->incidencia($this->docente, 'INC-2026-041');
        $this->incidencia($this->docente, 'INC-2026-042', ['estado' => 'cerrada', 'solucion' => 'Listo']);
        $this->incidencia($this->crearUsuario(Role::USUARIO), 'INC-2026-043');

        $this->escribir(self::NUMERO, 'mis incidencias')->assertOk();

        $respuesta = $this->ultimaRespuesta();
        $this->assertStringContainsString('INC-2026-041', $respuesta);
        $this->assertStringNotContainsString('INC-2026-042', $respuesta);
        $this->assertStringNotContainsString('INC-2026-043', $respuesta);
    }

    public function test_usa_gemini_para_interpretar_el_lenguaje_natural(): void
    {
        $this->configurarWhatsApp(conGemini: true);
        $this->fingirApis(['intencion' => 'consultar_dispositivo', 'codigo_dispositivo' => 'PC-LAB1-05']);
        $this->dispositivo();

        $this->escribir(self::NUMERO, 'oye, la compu del lab 1 número 5 ya está lista? código PC-LAB1-05')->assertOk();

        $this->assertStringContainsString('En mantenimiento', $this->ultimaRespuesta());
        $this->assertDatabaseHas('whatsapp_mensajes', ['direccion' => 'entrante', 'interprete' => 'gemini', 'intencion' => 'consultar_dispositivo']);
    }

    public function test_si_gemini_propone_un_codigo_inexistente_no_inventa_el_estado(): void
    {
        $this->configurarWhatsApp(conGemini: true);
        $this->fingirApis(['intencion' => 'consultar_dispositivo', 'codigo_dispositivo' => 'PC-INVENTADA']);
        $this->dispositivo();

        $this->escribir(self::NUMERO, 'como está la computadora de la dirección?')->assertOk();

        $this->assertStringContainsString('No encontré un dispositivo con el código *PC-INVENTADA*', $this->ultimaRespuesta());
    }

    public function test_si_gemini_no_responde_se_usan_las_reglas(): void
    {
        $this->configurarWhatsApp(conGemini: true);
        $this->fingirApis(503);
        $this->dispositivo();

        $this->escribir(self::NUMERO, 'estado de PC-LAB1-05')->assertOk();

        $this->assertStringContainsString('En mantenimiento', $this->ultimaRespuesta());
        $this->assertDatabaseHas('whatsapp_mensajes', ['direccion' => 'entrante', 'interprete' => 'reglas']);
    }

    public function test_a_gemini_solo_se_envia_el_texto_del_mensaje(): void
    {
        $this->configurarWhatsApp(conGemini: true);
        $this->fingirApis(['intencion' => 'saludo']);

        $this->escribir(self::NUMERO, 'buenos días')->assertOk();

        Http::assertSent(function ($r) {
            if (! str_contains($r->url(), 'generativelanguage.googleapis.com')) {
                return false;
            }
            $cuerpo = json_encode($r->data(), JSON_UNESCAPED_UNICODE);

            return $r->hasHeader('x-goog-api-key', 'clave-gemini-de-prueba')
                && str_contains($cuerpo, 'buenos días')
                && ! str_contains($cuerpo, 'Ana Torres')
                && ! str_contains($cuerpo, self::NUMERO);
        });
    }

    public function test_un_mensaje_no_entendido_muestra_ejemplos(): void
    {
        $this->escribir(self::NUMERO, 'qwerty asdf')->assertOk();

        $this->assertStringContainsString('No entendí tu mensaje', $this->ultimaRespuesta());
        $this->assertStringContainsString('mis incidencias', $this->ultimaRespuesta());
    }
}
