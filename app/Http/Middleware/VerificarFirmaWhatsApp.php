<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sprint 5 - RNF02: solo se procesan las notificaciones firmadas por Meta.
 * Meta firma el cuerpo de cada webhook con la clave secreta de la app (HMAC-SHA256)
 * y envía el resultado en la cabecera X-Hub-Signature-256.
 */
class VerificarFirmaWhatsApp
{
    public function handle(Request $request, Closure $next): Response
    {
        $secreto = (string) config('services.whatsapp.app_secret');
        $firma = (string) $request->header('X-Hub-Signature-256');

        if ($secreto === '' || $firma === '') {
            Log::warning('WhatsApp: webhook rechazado (sin firma o sin WHATSAPP_APP_SECRET configurado).');
            abort(403, 'Firma no válida.');
        }

        $esperada = 'sha256='.hash_hmac('sha256', $request->getContent(), $secreto);

        if (! hash_equals($esperada, $firma)) {
            Log::warning('WhatsApp: webhook rechazado (firma no coincide).', ['ip' => $request->ip()]);
            abort(403, 'Firma no válida.');
        }

        return $next($request);
    }
}
