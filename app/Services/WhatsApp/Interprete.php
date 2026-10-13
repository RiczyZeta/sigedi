<?php

namespace App\Services\WhatsApp;

use App\Models\Dispositivo;
use App\Models\Incidencia;
use Illuminate\Support\Str;

/**
 * Sprint 5 - Interpreta el mensaje del usuario (HU18, HU20).
 *
 * 1. Las reglas locales siempre se ejecutan: detectan los códigos de incidencia (INC-AAAA-NNN)
 *    y los códigos patrimoniales que EXISTEN en la base de datos.
 * 2. Si Gemini está configurado, aporta la intención, la descripción y la categoría de la falla.
 *    Si Gemini no responde, se usa la intención calculada por las reglas (el asistente no se detiene).
 */
class Interprete
{
    public const INTENCIONES = [
        'saludo',
        'ayuda',
        'consultar_dispositivo',
        'consultar_incidencia',
        'mis_incidencias',
        'reportar_incidencia',
        'confirmar',
        'cancelar',
        'desconocida',
    ];

    private const PALABRAS_FALLA = [
        'reportar', 'reporto', 'reporte', 'falla', 'fallo', 'no funciona', 'no enciende', 'no prende', 'no imprime',
        'no carga', 'no conecta', 'no hay internet', 'sin internet', 'malogr', 'averi', 'problema', 'error', 'roto',
        'rota', 'se apaga', 'se cuelga', 'lento', 'pantalla azul', 'no da imagen', 'no responde',
    ];

    public function __construct(private readonly GeminiCliente $gemini)
    {
    }

    /**
     * @return array{intencion: string, codigo_dispositivo: ?string, codigo_incidencia: ?string,
     *               descripcion: ?string, categoria: ?string, interprete: string, codigo_mencionado: ?string}
     */
    public function interpretar(string $texto, string $paso = 'inicio'): array
    {
        $reglas = $this->porReglas($texto, $paso);
        $ia = $this->gemini->clasificar($texto, $paso);

        if ($ia === null) {
            return $reglas;
        }

        // Los códigos se validan contra la base de datos: un código propuesto por la IA que no existe
        // se conserva solo como «mencionado», para responder que no se encontró (RNF08).
        $codigoIa = $this->limpiarCodigo($ia['codigo_dispositivo'] ?? null);
        $dispositivoIa = $codigoIa ? Dispositivo::whereRaw('UPPER(codigo_patrimonial) = ?', [$codigoIa])->value('codigo_patrimonial') : null;
        $incidenciaIa = $this->limpiarCodigo($ia['codigo_incidencia'] ?? null);

        return [
            'intencion' => $ia['intencion'],
            'codigo_dispositivo' => $reglas['codigo_dispositivo'] ?? $dispositivoIa,
            'codigo_incidencia' => $reglas['codigo_incidencia']
                ?? (preg_match('/^INC-\d{4}-\d{3,}$/', (string) $incidenciaIa) ? $incidenciaIa : null),
            'descripcion' => filled($ia['descripcion'] ?? null) ? Str::limit(trim($ia['descripcion']), 1000) : $reglas['descripcion'],
            'categoria' => in_array($ia['categoria'] ?? null, Incidencia::CATEGORIAS, true) ? $ia['categoria'] : $reglas['categoria'],
            'interprete' => 'gemini',
            'codigo_mencionado' => $reglas['codigo_mencionado'] ?? $codigoIa,
        ];
    }

    /** Interpretación sin IA: palabras clave y expresiones regulares. */
    public function porReglas(string $texto, string $paso = 'inicio'): array
    {
        $plano = Str::of($texto)->lower()->ascii()->squish()->toString();

        $codigoIncidencia = preg_match('/\bINC-\d{4}-\d{3,}\b/i', $texto, $m) ? strtoupper($m[0]) : null;
        $codigoDispositivo = $this->buscarCodigoDispositivo($texto);
        $mencionado = $codigoDispositivo ?? $this->codigoCandidato($texto, $codigoIncidencia);
        $esFalla = Str::contains($plano, self::PALABRAS_FALLA);

        $intencion = match (true) {
            (bool) preg_match('/^(si|s|ok|okey|confirmo|confirmar|dale|correcto|de acuerdo|claro)\b/', $plano) && $paso !== 'inicio' => 'confirmar',
            (bool) preg_match('/^(no|nop|nel)[\s,.!]*$/', $plano),
            (bool) preg_match('/^(cancelar|cancela|cancelo|olvidalo|salir)\b/', $plano) => 'cancelar',
            Str::contains($plano, ['mis incidencias', 'mis reportes', 'mis tickets', 'mis casos']) => 'mis_incidencias',
            $esFalla => 'reportar_incidencia',
            $codigoIncidencia !== null => 'consultar_incidencia',
            $mencionado !== null || Str::contains($plano, ['estado del', 'estado de', 'como esta', 'donde esta', 'consultar']) => 'consultar_dispositivo',
            Str::contains($plano, ['ayuda', 'menu', 'opciones', 'que puedes', 'como funciona']) => 'ayuda',
            (bool) preg_match('/^(hola|buenos|buenas|buen dia|saludos)\b/', $plano) => 'saludo',
            default => 'desconocida',
        };

        return [
            'intencion' => $intencion,
            'codigo_dispositivo' => $codigoDispositivo,
            'codigo_incidencia' => $codigoIncidencia,
            'descripcion' => $esFalla ? Str::limit(trim($texto), 1000) : null,
            'categoria' => $esFalla ? $this->categoriaPorReglas($plano) : null,
            'interprete' => 'reglas',
            'codigo_mencionado' => $mencionado,
        ];
    }

    /** Busca en el texto un código patrimonial registrado (sin distinguir mayúsculas; el más largo primero). */
    public function buscarCodigoDispositivo(string $texto): ?string
    {
        $codigos = Dispositivo::query()->pluck('codigo_patrimonial')
            ->sortByDesc(fn ($c) => strlen($c));

        foreach ($codigos as $codigo) {
            if (preg_match('/(?<![A-Z0-9-])'.preg_quote($codigo, '/').'(?![A-Z0-9-])/i', $texto)) {
                return $codigo;
            }
        }

        return null;
    }

    /** Texto con forma de código (letras/números separados por guiones) que no existe en el inventario. */
    private function codigoCandidato(string $texto, ?string $codigoIncidencia): ?string
    {
        if (! preg_match_all('/\b[A-Z0-9]+(?:-[A-Z0-9]+)+\b/i', $texto, $m)) {
            return null;
        }

        foreach ($m[0] as $token) {
            if (strtoupper($token) !== $codigoIncidencia && preg_match('/\d/', $token)) {
                return strtoupper($token);
            }
        }

        return null;
    }

    private function categoriaPorReglas(string $plano): string
    {
        return match (true) {
            Str::contains($plano, ['imprim', 'impresora', 'toner', 'tinta', 'papel']) => 'Impresión',
            Str::contains($plano, ['internet', 'wifi', ' red', 'conect', 'router']) => 'Red e Internet',
            Str::contains($plano, ['mouse', 'teclado', 'parlante', 'audifono', 'camara', 'proyector', 'usb']) => 'Periféricos',
            Str::contains($plano, ['programa', 'windows', 'virus', 'office', 'word', 'excel', 'sistema', 'pantalla azul', 'lento', 'se cuelga']) => 'Software',
            Str::contains($plano, ['enciende', 'prende', 'apaga', 'pantalla', 'monitor', 'disco', 'fuente', 'ruido', 'bateria', 'cargador']) => 'Hardware',
            default => 'Otro',
        };
    }

    private function limpiarCodigo(?string $codigo): ?string
    {
        $codigo = strtoupper(trim((string) $codigo));

        return $codigo !== '' ? $codigo : null;
    }
}
