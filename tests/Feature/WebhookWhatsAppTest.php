<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\WhatsappMensaje;
use App\Support\Telefono;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreaUsuarios;
use Tests\Concerns\SimulaWhatsApp;
use Tests\TestCase;

/** HU17 - Conexión con WhatsApp Business Cloud API (Sprint 5). */
class WebhookWhatsAppTest extends TestCase
{
    use RefreshDatabase, CreaUsuarios, SimulaWhatsApp;

    private const NUMERO = '51987654321';

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurarWhatsApp();
        $this->fingirApis();
    }

    public function test_el_webhook_supera_la_verificacion_de_meta(): void
    {
        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=verificacion-sigedi&hub.challenge=1158201444')
            ->assertOk()
            ->assertSeeText('1158201444');
    }

    public function test_la_verificacion_falla_con_un_token_incorrecto(): void
    {
        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=otro&hub.challenge=123')
            ->assertForbidden();
    }

    public function test_se_rechazan_los_mensajes_sin_firma_o_con_firma_invalida(): void
    {
        $this->crearUsuario(Role::USUARIO, ['whatsapp' => '+'.self::NUMERO]);
        $payload = $this->payload(self::NUMERO, 'hola');

        $this->call('POST', '/webhook/whatsapp', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload))
            ->assertForbidden();
        $this->enviarWebhook($payload, 'sha256=firmafalsa')->assertForbidden();

        $this->assertSame(0, WhatsappMensaje::count());
        Http::assertNothingSent();
    }

    public function test_un_mensaje_firmado_se_recibe_y_se_responde_en_el_mismo_chat(): void
    {
        $this->crearUsuario(Role::USUARIO, ['name' => 'Rosa Docente', 'whatsapp' => '987654321']);

        $this->escribir(self::NUMERO, 'Hola')->assertOk()->assertSeeText('EVENT_RECEIVED');

        Http::assertSent(fn ($r) => $r->url() === 'https://graph.facebook.com/v23.0/123456789/messages'
            && $r->hasHeader('Authorization', 'Bearer token-de-prueba')
            && $r['to'] === self::NUMERO
            && $r['messaging_product'] === 'whatsapp');
        $this->assertStringContainsString('Hola, Rosa', $this->ultimaRespuesta());
        $this->assertDatabaseHas('whatsapp_mensajes', ['direccion' => 'entrante', 'telefono' => self::NUMERO, 'intencion' => 'saludo']);
        $this->assertDatabaseHas('whatsapp_mensajes', ['direccion' => 'saliente', 'estado_envio' => 'enviado']);
    }

    public function test_un_mensaje_repetido_por_meta_se_procesa_una_sola_vez(): void
    {
        $this->crearUsuario(Role::USUARIO, ['whatsapp' => '+'.self::NUMERO]);

        $this->escribir(self::NUMERO, 'ayuda', 'wamid.REPETIDO')->assertOk();
        $this->escribir(self::NUMERO, 'ayuda', 'wamid.REPETIDO')->assertOk();

        $this->assertCount(1, $this->respuestasEnviadas());
        $this->assertSame(1, WhatsappMensaje::where('direccion', 'entrante')->count());
    }

    public function test_los_avisos_de_entrega_se_ignoran(): void
    {
        $payload = ['object' => 'whatsapp_business_account', 'entry' => [['changes' => [['value' => [
            'statuses' => [['id' => 'wamid.x', 'status' => 'delivered', 'recipient_id' => self::NUMERO]],
        ]]]]]];

        $this->enviarWebhook($payload)->assertOk();
        Http::assertNothingSent();
    }

    public function test_los_mensajes_que_no_son_texto_reciben_una_indicacion(): void
    {
        $this->crearUsuario(Role::USUARIO, ['whatsapp' => '+'.self::NUMERO]);

        $this->enviarWebhook($this->payload(self::NUMERO, '', null, 'audio'))->assertOk();

        $this->assertStringContainsString('solo puedo leer mensajes de texto', $this->ultimaRespuesta());
    }

    public function test_un_numero_no_registrado_no_obtiene_informacion(): void
    {
        $this->escribir('51900000000', 'estado de PC-001')->assertOk();

        $this->assertStringContainsString('no está registrado en SIGEDI', $this->ultimaRespuesta());
        $this->assertDatabaseHas('whatsapp_mensajes', ['direccion' => 'entrante', 'user_id' => null, 'intencion' => null]);
    }

    public function test_un_usuario_desactivado_no_es_reconocido(): void
    {
        $this->crearUsuario(Role::USUARIO, ['whatsapp' => '+'.self::NUMERO, 'activo' => false]);

        $this->escribir(self::NUMERO, 'hola')->assertOk();

        $this->assertStringContainsString('no está registrado en SIGEDI', $this->ultimaRespuesta());
    }

    public function test_los_numeros_se_normalizan(): void
    {
        $this->assertSame('51987654321', Telefono::normalizar('+51 987 654 321'));
        $this->assertSame('51987654321', Telefono::normalizar('987654321'));
        $this->assertTrue(Telefono::iguales('+51987654321', '51987654321'));
        $this->assertSame('51987***321', Telefono::enmascarar('51987654321'));
    }

    public function test_solo_el_administrador_ve_el_panel_del_asistente(): void
    {
        $admin = $this->crearUsuario(Role::ADMINISTRADOR);
        $tecnico = $this->crearUsuario(Role::TECNICO);

        $this->actingAs($admin)->get(route('asistente.index'))
            ->assertOk()
            ->assertSee('Conexión configurada')
            ->assertSee('/webhook/whatsapp');

        $this->actingAs($tecnico)->get(route('asistente.index'))->assertForbidden();
    }
}
