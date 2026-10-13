<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\AsistenteWhatsApp;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sprint 5 - HU17: webhook de WhatsApp Business Cloud API.
 * GET  /webhook/whatsapp  verificación del webhook al configurarlo en Meta for Developers.
 * POST /webhook/whatsapp  mensajes entrantes (la firma la valida VerificarFirmaWhatsApp).
 */
class WebhookWhatsAppController extends Controller
{
    public function verificar(Request $request): Response
    {
        $token = (string) config('services.whatsapp.verify_token');

        if ($request->query('hub_mode') === 'subscribe'
            && $token !== ''
            && hash_equals($token, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        return response('Token de verificación no válido.', 403);
    }

    public function recibir(Request $request, AsistenteWhatsApp $asistente): Response
    {
        foreach ((array) $request->json('entry', []) as $entrada) {
            foreach ((array) ($entrada['changes'] ?? []) as $cambio) {
                // Solo interesan los mensajes; los avisos de entrega y lectura (statuses) se ignoran.
                foreach ((array) ($cambio['value']['messages'] ?? []) as $mensaje) {
                    try {
                        $tipo = (string) ($mensaje['type'] ?? 'text');

                        $asistente->procesar(
                            telefono: (string) ($mensaje['from'] ?? ''),
                            texto: $tipo === 'text' ? (string) ($mensaje['text']['body'] ?? '') : '',
                            waMessageId: $mensaje['id'] ?? null,
                            tipo: $tipo,
                        );
                    } catch (Throwable $e) {
                        // Se responde 200 igualmente para que Meta no reintente un mensaje con error.
                        Log::error('WhatsApp: error al procesar el mensaje', ['error' => $e->getMessage()]);
                        report($e);
                    }
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }
}
