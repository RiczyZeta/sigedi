<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sprint 5 - HU18, HU20: interpretación del lenguaje natural con Gemini API (capa gratuita).
 *
 * Gemini solo CLASIFICA el mensaje y EXTRAE datos (intención, códigos, descripción de la falla).
 * Las respuestas al usuario se construyen siempre con datos de la base de datos, por lo que el
 * asistente no puede inventar información (RNF08, riesgo R03). Al modelo solo se envía el texto
 * del mensaje: nunca nombres, correos ni números de teléfono (riesgo R08).
 */
class GeminiCliente
{
    private const MAX_CARACTERES = 1000;   // limita el consumo de la capa gratuita (riesgo R06)

    public function configurado(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /** @return array<string, mixed>|null  null si Gemini no está disponible o responde algo inválido */
    public function clasificar(string $texto, string $paso): ?array
    {
        if (! $this->configurado()) {
            return null;
        }

        $modelo = config('services.gemini.model', 'gemini-flash-latest');

        try {
            $respuesta = Http::withHeaders(['x-goog-api-key' => config('services.gemini.key')])
                ->acceptJson()
                ->timeout(6)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent", [
                    'systemInstruction' => ['parts' => [['text' => $this->instrucciones($paso)]]],
                    'contents' => [['role' => 'user', 'parts' => [['text' => Str::limit($texto, self::MAX_CARACTERES)]]]],
                    'generationConfig' => [
                        'temperature' => 0,
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $this->esquema(),
                    ],
                ]);

            if (! $respuesta->successful()) {
                Log::warning('Gemini: respuesta no exitosa', ['status' => $respuesta->status()]);

                return null;
            }

            $json = json_decode((string) $respuesta->json('candidates.0.content.parts.0.text'), true);

            return is_array($json) && in_array($json['intencion'] ?? null, Interprete::INTENCIONES, true) ? $json : null;
        } catch (Throwable $e) {
            Log::warning('Gemini: error de conexión', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function instrucciones(string $paso): string
    {
        $intenciones = implode(', ', Interprete::INTENCIONES);

        return <<<TXT
        Eres el clasificador de mensajes del asistente de WhatsApp de SIGEDI, el sistema de gestión de dispositivos
        tecnológicos e incidencias del área de Cómputo de la I.E. INA 54 (Tambogrande, Perú).
        Analiza el mensaje del usuario y devuelve SOLO un JSON con estos campos:
        - intencion: una de [{$intenciones}].
          * consultar_dispositivo: pregunta por el estado o la ubicación de un equipo.
          * consultar_incidencia: pregunta por un ticket con formato INC-AAAA-NNN.
          * mis_incidencias: quiere ver sus incidencias o reportes.
          * reportar_incidencia: informa una falla o pide registrar un problema.
          * confirmar / cancelar: responde sí o no a una confirmación.
          * saludo, ayuda o desconocida en los demás casos.
        - codigo_dispositivo: código patrimonial EXACTAMENTE como aparece en el mensaje (por ejemplo PC-LAB1-05), o null.
        - codigo_incidencia: código INC-AAAA-NNN tal como aparece, o null.
        - descripcion: si reporta una falla, una descripción breve de la falla en español, sin nombres de personas; si no, null.
        - categoria: Hardware, Software, Red e Internet, Impresión, Periféricos u Otro si reporta una falla; si no, null.
        Nunca inventes códigos ni datos: si el mensaje no contiene un código, usa null.
        Paso actual de la conversación: {$paso}. Si el paso es confirmar_reporte, un «sí» es confirmar y un «no» es cancelar.
        TXT;
    }

    private function esquema(): array
    {
        $texto = ['type' => 'STRING', 'nullable' => true];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'intencion' => ['type' => 'STRING', 'enum' => Interprete::INTENCIONES],
                'codigo_dispositivo' => $texto,
                'codigo_incidencia' => $texto,
                'descripcion' => $texto,
                'categoria' => $texto,
            ],
            'required' => ['intencion'],
        ];
    }
}
