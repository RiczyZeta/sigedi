<?php

namespace Tests\Concerns;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

/** Sprint 5 - Utilidades para probar el webhook de WhatsApp sin llamar a Meta ni a Gemini. */
trait SimulaWhatsApp
{
    protected string $secretoApp = 'secreto-de-prueba';

    protected function configurarWhatsApp(bool $conGemini = false): void
    {
        config([
            'services.whatsapp.token' => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.verify_token' => 'verificacion-sigedi',
            'services.whatsapp.app_secret' => $this->secretoApp,
            'services.whatsapp.api_version' => 'v23.0',
            'services.gemini.key' => $conGemini ? 'clave-gemini-de-prueba' : null,
            'services.gemini.model' => 'gemini-flash-latest',
        ]);
    }

    /** Respuesta simulada de Meta; la de Gemini puede indicarse como arreglo (JSON del clasificador) o código HTTP. */
    protected function fingirApis(array|int|null $gemini = null): void
    {
        // Se reinicia el cliente HTTP para que cada prueba pueda definir su propia respuesta de Gemini
        // (con Http::fake las primeras respuestas registradas tienen prioridad).
        Http::swap(new HttpFactory(app(Dispatcher::class)));

        Http::fake([
            'graph.facebook.com/*' => fn () => Http::response(['messages' => [['id' => 'wamid.saliente.'.uniqid('', true)]]], 200),
            'generativelanguage.googleapis.com/*' => is_int($gemini)
                ? Http::response(['error' => 'no disponible'], $gemini)
                : Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($gemini ?? ['intencion' => 'desconocida'])]]]]]], 200),
        ]);
    }

    protected function payload(string $de, string $texto, ?string $id = null, string $tipo = 'text'): array
    {
        $mensaje = ['from' => $de, 'id' => $id ?? 'wamid.'.uniqid(), 'timestamp' => (string) time(), 'type' => $tipo];
        $mensaje += $tipo === 'text' ? ['text' => ['body' => $texto]] : [$tipo => ['id' => 'media-1']];

        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'WABA-ID',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['phone_number_id' => '123456789'],
                        'contacts' => [['wa_id' => $de, 'profile' => ['name' => 'Contacto']]],
                        'messages' => [$mensaje],
                    ],
                ]],
            ]],
        ];
    }

    protected function enviarWebhook(array $payload, ?string $firma = null): TestResponse
    {
        $json = json_encode($payload);
        $firma ??= 'sha256='.hash_hmac('sha256', $json, $this->secretoApp);

        return $this->call('POST', '/webhook/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $firma,
        ], $json);
    }

    /** Envía un mensaje de texto firmado y devuelve la respuesta HTTP. */
    protected function escribir(string $de, string $texto, ?string $id = null): TestResponse
    {
        return $this->enviarWebhook($this->payload($de, $texto, $id));
    }

    /** Textos enviados a Meta, en orden. */
    protected function respuestasEnviadas(?string $a = null): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'graph.facebook.com')
                && ($a === null || $r['to'] === $a))
            ->map(fn ($par) => $par[0]['text']['body'])
            ->values()
            ->all();
    }

    protected function ultimaRespuesta(?string $a = null): string
    {
        $respuestas = $this->respuestasEnviadas($a);

        return (string) end($respuestas);
    }
}
