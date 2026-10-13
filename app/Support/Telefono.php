<?php

namespace App\Support;

/**
 * Sprint 5 - Normalización de números de WhatsApp.
 * Meta envía el número sin «+» y con código de país (51987654321); en SIGEDI se registra
 * como «+51987654321» o «987654321». Ambos formatos se comparan en su forma normalizada.
 */
class Telefono
{
    public const CODIGO_PAIS = '51';

    public static function normalizar(?string $numero): string
    {
        $digitos = preg_replace('/\D+/', '', (string) $numero);

        // Celular peruano de 9 dígitos sin código de país
        if (strlen($digitos) === 9 && str_starts_with($digitos, '9')) {
            return self::CODIGO_PAIS.$digitos;
        }

        return $digitos;
    }

    public static function iguales(?string $a, ?string $b): bool
    {
        $a = self::normalizar($a);

        return $a !== '' && $a === self::normalizar($b);
    }

    /** Oculta los dígitos centrales para mostrar el número en pantalla: 51987***321. */
    public static function enmascarar(?string $numero): string
    {
        $n = self::normalizar($numero);

        return strlen($n) > 6 ? substr($n, 0, 5).str_repeat('*', strlen($n) - 8).substr($n, -3) : $n;
    }
}
