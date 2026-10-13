<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Dispositivo;
use App\Models\Incidencia;
use App\Models\Role;
use App\Models\Ubicacion;
use App\Models\User;
use App\Models\WhatsappConversacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaUsuarios;
use Tests\Concerns\SimulaWhatsApp;
use Tests\TestCase;

/** HU20 - Reporte de incidencias por WhatsApp con número de ticket (Sprint 5). */
class ReporteWhatsAppTest extends TestCase
{
    use RefreshDatabase, CreaUsuarios, SimulaWhatsApp;

    private const NUMERO = '51987654321';

    private User $docente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurarWhatsApp();
        $this->fingirApis();
        $this->docente = $this->crearUsuario(Role::USUARIO, ['whatsapp' => '+'.self::NUMERO]);
    }

    private function impresora(string $estado = 'operativo'): Dispositivo
    {
        return Dispositivo::create([
            'codigo_patrimonial' => 'IMP-DIR-01',
            'tipo' => 'Impresora',
            'estado' => $estado,
            'ubicacion_id' => Ubicacion::firstOrCreate(['nombre' => 'Dirección'])->id,
        ]);
    }

    public function test_el_asistente_confirma_el_dispositivo_antes_de_registrar(): void
    {
        $this->impresora();

        $this->escribir(self::NUMERO, 'La impresora IMP-DIR-01 no imprime, sale la hoja en blanco')->assertOk();

        $respuesta = $this->ultimaRespuesta();
        $this->assertStringContainsString('IMP-DIR-01', $respuesta);
        $this->assertStringContainsString('Dirección', $respuesta);
        $this->assertStringContainsString('Responde *SÍ*', $respuesta);
        $this->assertSame(0, Incidencia::count());
        $this->assertSame(WhatsappConversacion::CONFIRMAR_REPORTE, WhatsappConversacion::first()->paso);
    }

    public function test_al_confirmar_se_crea_la_incidencia_con_canal_whatsapp_y_se_devuelve_el_ticket(): void
    {
        $impresora = $this->impresora();

        $this->escribir(self::NUMERO, 'La impresora IMP-DIR-01 no imprime, sale la hoja en blanco');
        $this->escribir(self::NUMERO, 'Sí');

        $incidencia = Incidencia::firstOrFail();
        $this->assertSame('whatsapp', $incidencia->canal);
        $this->assertSame('registrada', $incidencia->estado);
        $this->assertSame($this->docente->id, $incidencia->solicitante_id);
        $this->assertSame($impresora->id, $incidencia->dispositivo_id);
        $this->assertSame('Impresión', $incidencia->categoria);
        $this->assertStringContainsString('sale la hoja en blanco', $incidencia->descripcion);
        $this->assertStringContainsString('*'.$incidencia->codigo.'*', $this->ultimaRespuesta());
        $this->assertSame(WhatsappConversacion::INICIO, WhatsappConversacion::first()->paso);
    }

    public function test_al_responder_no_se_cancela_el_reporte(): void
    {
        $this->impresora();

        $this->escribir(self::NUMERO, 'La impresora IMP-DIR-01 no imprime, sale la hoja en blanco');
        $this->escribir(self::NUMERO, 'no');

        $this->assertSame(0, Incidencia::count());
        $this->assertStringContainsString('Reporte cancelado', $this->ultimaRespuesta());
    }

    public function test_si_falta_el_codigo_lo_pide_y_continua_el_reporte(): void
    {
        $this->impresora();

        $this->escribir(self::NUMERO, 'La impresora de la dirección no imprime nada desde ayer');
        $this->assertStringContainsString('código patrimonial', $this->ultimaRespuesta());

        $this->escribir(self::NUMERO, 'imp-dir-01');
        $this->assertStringContainsString('Responde *SÍ*', $this->ultimaRespuesta());

        $this->escribir(self::NUMERO, 'si');
        $this->assertSame(1, Incidencia::where('canal', 'whatsapp')->count());
    }

    public function test_si_la_descripcion_es_muy_corta_la_pide(): void
    {
        $this->impresora();

        $this->escribir(self::NUMERO, 'falla IMP-DIR-01');
        $this->assertStringContainsString('Describe brevemente la falla', $this->ultimaRespuesta());

        $this->escribir(self::NUMERO, 'Atasca el papel en cada impresión');
        $this->assertStringContainsString('Responde *SÍ*', $this->ultimaRespuesta());

        $this->escribir(self::NUMERO, 'ok');
        $this->assertSame('Atasca el papel en cada impresión', Incidencia::firstOrFail()->descripcion);
    }

    public function test_no_se_reportan_equipos_dados_de_baja(): void
    {
        $this->impresora('de_baja');

        $this->escribir(self::NUMERO, 'La impresora IMP-DIR-01 no imprime nada');

        $this->assertStringContainsString('dado de baja', $this->ultimaRespuesta());
        $this->assertSame(0, Incidencia::count());
    }

    public function test_un_codigo_inexistente_no_genera_la_incidencia(): void
    {
        $this->impresora();

        $this->escribir(self::NUMERO, 'La impresora IMP-XYZ-77 no imprime nada');

        $this->assertStringContainsString('No encontré el código *IMP-XYZ-77*', $this->ultimaRespuesta());
        $this->assertSame(0, Incidencia::count());
    }

    public function test_la_incidencia_queda_en_la_bitacora_con_el_usuario_que_escribio(): void
    {
        $this->impresora();

        $this->escribir(self::NUMERO, 'La impresora IMP-DIR-01 no imprime, sale la hoja en blanco');
        $this->escribir(self::NUMERO, 'sí');

        $registro = Bitacora::where('accion', 'registro_incidencia')->firstOrFail();
        $this->assertSame($this->docente->id, $registro->user_id);
        $this->assertStringContainsString('canal whatsapp', $registro->descripcion);
    }

    public function test_la_incidencia_aparece_en_el_sistema_web_del_solicitante(): void
    {
        $this->impresora();
        $this->escribir(self::NUMERO, 'La impresora IMP-DIR-01 no imprime, sale la hoja en blanco');
        $this->escribir(self::NUMERO, 'sí');
        $codigo = Incidencia::firstOrFail()->codigo;

        $this->actingAs($this->docente)->get(route('incidencias.index'))->assertOk()->assertSee($codigo);
    }

    public function test_una_conversacion_vencida_se_reinicia(): void
    {
        $this->impresora();
        $this->escribir(self::NUMERO, 'La impresora IMP-DIR-01 no imprime, sale la hoja en blanco');

        $this->travel(WhatsappConversacion::MINUTOS_VIGENCIA + 1)->minutes();
        $this->escribir(self::NUMERO, 'sí');

        $this->assertSame(0, Incidencia::count());
    }
}
