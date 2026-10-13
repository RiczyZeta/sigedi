<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sprint 4 - Bitácora digital de actividades (HU13, HU14).
 * Solo consulta: los registros no se pueden editar ni eliminar (no existen rutas para hacerlo
 * y el modelo Bitacora lo impide). Acceso: administrador y técnico.
 */
class BitacoraController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'tipo' => ['nullable', 'string'],
            'accion' => ['nullable', 'string'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ], [
            'desde.date' => 'La fecha «desde» no es válida.',
            'hasta.date' => 'La fecha «hasta» no es válida.',
            'hasta.after_or_equal' => 'La fecha «hasta» debe ser igual o posterior a la fecha «desde».',
        ]);

        $registros = Bitacora::with(['usuario', 'dispositivo', 'incidencia'])
            ->filtrar($filtros)
            ->recientes()
            ->paginate(20)
            ->withQueryString();

        return view('bitacora.index', [
            'registros' => $registros,
            'filtros' => $filtros,
            'hayFiltros' => collect($filtros)->filter(fn ($v) => filled($v))->isNotEmpty(),
        ]);
    }
}
