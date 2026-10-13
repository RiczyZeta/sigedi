<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappMensaje;
use App\Support\Telefono;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sprint 5 - HU17: envío de mensajes de texto mediante WhatsApp Business Cloud API.
 * Las respuestas se envían dentro de la ventana de 24 horas que abre el usuario al escribir,
 * por lo que no se usan plantillas con costo (Tabla 14).
 */
class WhatsAppCliente
{
    /** Límite de caracteres de un mensaje de texto en WhatsApp. */
    private const MAX_CARACTERES = 4096;

    /** En modo simulado no se llama a Meta (comando whatsapp:simular y entorno sin credenciales). */
    private bool $simular = false;

    public function simular(bool $simular = true): static
    {
        $this->simular = $simular;

        return $this;
    }

    public function configurado(): bool
    {
        return filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id'));
    }

    public function enviarTexto(string $telefono, string $texto, ?int $userId = null): WhatsappMensaje
    {
        $telefono = Telefono::normalizar($telefono);
        $texto = Str::limit($texto, self::MAX_CARACTERES - 3);

        $registro = WhatsappMensaje::create([
            'telefono' => $telefono,
            'user_id' => $userId,
            'direccion' => WhatsappMensaje::SALIENTE,
            'tipo' => 'text',
            'contenido' => $texto,
            'estado_envio' => 'simulado',
        ]);

        if ($this->simular || ! $this->configurado()) {
            return $registro;
        }

        try {
            $respuesta = Http::withToken(config('services.whatsapp.token'))
                ->acceptJson()
                ->timeout(8)
                ->post($this->url(), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $telefono,
                    'type' => 'text',
                    'text' => ['preview_url' => false, 'body' => $texto],
                ]);

            $registro->update($respuesta->successful()
                ? ['estado_envio' => 'enviado', 'wa_message_id' => $respuesta->json('messages.0.id')]
                : ['estado_envio' => 'error', 'error' => Str::limit($respuesta->body(), 500)]);
        } catch (Throwable $e) {
            Log::warning('WhatsApp: no se pudo enviar el mensaje', ['error' => $e->getMessage()]);
            $registro->update(['estado_envio' => 'error', 'error' => Str::limit($e->getMessage(), 500)]);
        }

        return $registro;
    }

    private function url(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            config('services.whatsapp.api_version', 'v23.0'),
            config('services.whatsapp.phone_number_id'),
        );
    }
}
