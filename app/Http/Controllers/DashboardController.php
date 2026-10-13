<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\Incidencia;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Panel de inicio: contadores rápidos según el rol del usuario. */
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $abiertas = fn () => Incidencia::where('estado', '!=', 'cerrada');

        if ($usuario->tieneRol(Role::USUARIO)) {
            $stats = [
                ['etiqueta' => 'Mis incidencias abiertas', 'valor' => $abiertas()->where('solicitante_id', $usuario->id)->count(), 'icono' => 'bi-hourglass-split', 'color' => 'warning'],
                ['etiqueta' => 'Mis incidencias cerradas', 'valor' => Incidencia::where('estado', 'cerrada')->where('solicitante_id', $usuario->id)->count(), 'icono' => 'bi-check-circle-fill', 'color' => 'success'],
            ];
        } elseif ($usuario->tieneRol(Role::TECNICO)) {
            $stats = [
                ['etiqueta' => 'Asignadas a mí (abiertas)', 'valor' => $abiertas()->where('tecnico_id', $usuario->id)->count(), 'icono' => 'bi-wrench-adjustable', 'color' => 'primary'],
                ['etiqueta' => 'Incidencias abiertas', 'valor' => $abiertas()->count(), 'icono' => 'bi-exclamation-triangle-fill', 'color' => 'warning'],
                ['etiqueta' => 'Dispositivos activos', 'valor' => Dispositivo::where('estado', '!=', 'de_baja')->count(), 'icono' => 'bi-pc-display', 'color' => 'success'],
            ];
        } else {
            $stats = [
                ['etiqueta' => 'Dispositivos activos', 'valor' => Dispositivo::where('estado', '!=', 'de_baja')->count(), 'icono' => 'bi-pc-display', 'color' => 'success'],
                ['etiqueta' => 'Incidencias abiertas', 'valor' => $abiertas()->count(), 'icono' => 'bi-exclamation-triangle-fill', 'color' => 'warning'],
                ['etiqueta' => 'Críticas abiertas', 'valor' => $abiertas()->where('prioridad', 'critica')->count(), 'icono' => 'bi-fire', 'color' => 'danger'],
                ['etiqueta' => 'Sin asignar', 'valor' => Incidencia::where('estado', 'registrada')->count(), 'icono' => 'bi-person-dash-fill', 'color' => 'secondary'],
            ];
        }

        return view('panel', ['stats' => $stats]);
    }
}
