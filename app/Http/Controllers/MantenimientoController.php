<?php

namespace App\Http\Controllers;

use App\Http\Requests\MantenimientoRequest;
use App\Models\Bitacora;
use App\Models\Dispositivo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

/**
 * Sprint 4 - HU14: registro manual de mantenimientos preventivos y correctivos.
 * El registro se guarda en la bitácora con tipo «manual» y se muestra en la ficha del dispositivo (HU15).
 */
class MantenimientoController extends Controller
{
    public function store(MantenimientoRequest $request, Dispositivo $dispositivo): RedirectResponse
    {
        if ($dispositivo->estado === 'de_baja') {
            return back()->with('error', 'No se puede registrar mantenimiento de un dispositivo dado de baja.');
        }

        $datos = $request->validated();

        // Si el mantenimiento es de hoy se guarda con la hora actual; si es anterior, con la fecha indicada.
        $fecha = Carbon::parse($datos['fecha']);
        $fecha = $fecha->isToday() ? now() : $fecha->startOfDay();

        Bitacora::create([
            'user_id' => $request->user()->id,
            'dispositivo_id' => $dispositivo->id,
            'tipo' => Bitacora::TIPO_MANUAL,
            'accion' => Bitacora::MANTENIMIENTOS[$datos['tipo_mantenimiento']],
            'descripcion' => trim($datos['descripcion']),
            'fecha' => $fecha,
        ]);

        $tipo = $datos['tipo_mantenimiento'];

        return redirect()->route('dispositivos.show', $dispositivo)
            ->with('ok', "Mantenimiento {$tipo} registrado en la bitácora de {$dispositivo->codigo_patrimonial}.");
    }
}
