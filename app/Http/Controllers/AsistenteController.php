<?php

namespace App\Http\Controllers;

use App\Models\Incidencia;
use App\Models\WhatsappMensaje;
use App\Services\WhatsApp\GeminiCliente;
use App\Services\WhatsApp\WhatsAppCliente;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sprint 5 - HU17: panel del asistente de WhatsApp para el administrador.
 * Muestra el estado de la conexión, los indicadores de uso y los últimos mensajes.
 */
class AsistenteController extends Controller
{
    public function index(Request $request, WhatsAppCliente $whatsapp, GeminiCliente $gemini): View
    {
        $entrantes = WhatsappMensaje::where('direccion', WhatsappMensaje::ENTRANTE);
        $indicadores = [
            'recibidos' => (clone $entrantes)->count(),
            'hoy' => (clone $entrantes)->whereDate('created_at', today())->count(),
            'gemini' => (clone $entrantes)->where('interprete', 'gemini')->count(),
            'no_entendidos' => (clone $entrantes)->where('intencion', 'desconocida')->count(),
            'errores_envio' => WhatsappMensaje::where('estado_envio', 'error')->count(),
            'incidencias' => Incidencia::where('canal', 'whatsapp')->count(),
        ];

        return view('asistente.index', [
            'conexion' => [
                ['Token de acceso de WhatsApp', filled(config('services.whatsapp.token'))],
                ['Phone number ID', filled(config('services.whatsapp.phone_number_id'))],
                ['Token de verificación del webhook', filled(config('services.whatsapp.verify_token'))],
                ['Clave secreta de la app (firma de Meta)', filled(config('services.whatsapp.app_secret'))],
                ['API key de Gemini', $gemini->configurado()],
            ],
            'listo' => $whatsapp->configurado() && filled(config('services.whatsapp.app_secret')),
            'urlWebhook' => route('whatsapp.recibir'),
            'indicadores' => $indicadores,
            'tarjetas' => [
                ['Mensajes recibidos', $indicadores['recibidos'], 'bi-chat-dots-fill', 'primary'],
                ['Recibidos hoy', $indicadores['hoy'], 'bi-calendar-check', 'info'],
                ['Interpretados con Gemini', $indicadores['gemini'], 'bi-stars', 'success'],
                ['No entendidos', $indicadores['no_entendidos'], 'bi-question-circle-fill', 'warning'],
                ['Errores de envío', $indicadores['errores_envio'], 'bi-exclamation-octagon-fill', 'danger'],
                ['Incidencias por WhatsApp', $indicadores['incidencias'], 'bi-ticket-detailed-fill', 'secondary'],
            ],
            'mensajes' => WhatsappMensaje::with('usuario')->latest('id')->paginate(25),
        ]);
    }
}
