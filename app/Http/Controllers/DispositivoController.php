<?php

namespace App\Http\Controllers;

use App\Http\Requests\DispositivoRequest;
use App\Models\Bitacora;
use App\Models\Dispositivo;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Sprint 2 - Inventario de dispositivos (HU03, HU04, HU05).
 * Sprint 4 - Búsqueda (HU07) y ficha del dispositivo con su historial (HU15).
 * Acceso: administrador y técnico (lo controla RoleMiddleware en la ruta).
 * Un dispositivo no se elimina: se le cambia el estado a "de baja" para conservar su historial.
 */
class DispositivoController extends Controller
{
    /** HU07 (Sprint 4): búsqueda por código, tipo, ubicación y estado; filtros combinables y resultados paginados. */
    public function index(Request $request): View
    {
        $filtros = $request->only(['q', 'tipo', 'ubicacion_id', 'estado']);

        $dispositivos = Dispositivo::with(['ubicacion', 'responsable'])
            ->filtrar($filtros)
            ->orderBy('codigo_patrimonial')
            ->paginate(15)
            ->withQueryString();

        return view('dispositivos.index', [
            'dispositivos' => $dispositivos,
            'filtros' => $filtros,
            'hayFiltros' => collect($filtros)->filter(fn ($v) => filled($v))->isNotEmpty(),
            'tipos' => Dispositivo::query()->distinct()->orderBy('tipo')->pluck('tipo'),
            'ubicaciones' => Ubicacion::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /** HU15 (Sprint 4): ficha del dispositivo con sus incidencias y su bitácora, de la más reciente a la más antigua. */
    public function show(Dispositivo $dispositivo): View
    {
        $dispositivo->load(['ubicacion', 'responsable']);

        $incidencias = $dispositivo->incidencias()
            ->with(['solicitante', 'tecnico'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'pag_inc')
            ->withQueryString();

        $bitacora = $dispositivo->bitacoras()
            ->with(['usuario', 'incidencia'])
            ->recientes()
            ->paginate(15, ['*'], 'pag_bit')
            ->withQueryString();

        return view('dispositivos.show', [
            'dispositivo' => $dispositivo,
            'incidencias' => $incidencias,
            'bitacora' => $bitacora,
            'resumen' => [
                'incidencias' => $dispositivo->incidencias()->count(),
                'abiertas' => $dispositivo->incidencias()->where('estado', '!=', 'cerrada')->count(),
                'mantenimientos' => $dispositivo->bitacoras()->where('tipo', Bitacora::TIPO_MANUAL)->count(),
                'ultima' => $dispositivo->bitacoras()->max('fecha'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('dispositivos.create', $this->datosFormulario());
    }

    public function store(DispositivoRequest $request): RedirectResponse
    {
        Dispositivo::create($request->validated());

        return redirect()->route('dispositivos.index')->with('ok', 'Dispositivo registrado correctamente.');
    }

    public function edit(Dispositivo $dispositivo): View
    {
        return view('dispositivos.edit', ['dispositivo' => $dispositivo] + $this->datosFormulario());
    }

    public function update(DispositivoRequest $request, Dispositivo $dispositivo): RedirectResponse
    {
        $dispositivo->update($request->validated());

        return redirect()->route('dispositivos.index')->with('ok', 'Dispositivo actualizado correctamente.');
    }

    /** HU05: cambio rápido de estado desde el listado. */
    public function estado(Request $request, Dispositivo $dispositivo): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(Dispositivo::ESTADOS)],
        ], [
            'estado.required' => 'Selecciona un estado.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $dispositivo->update($datos);

        return back()->with('ok', "Estado de {$dispositivo->codigo_patrimonial} actualizado a «{$dispositivo->etiquetaEstado()}».");
    }

    /** Listas controladas para el formulario (HU04). */
    private function datosFormulario(): array
    {
        return [
            'ubicaciones' => Ubicacion::where('activo', true)->orderBy('nombre')->get(),
            'responsables' => User::where('activo', true)->orderBy('name')->get(),
        ];
    }
}
