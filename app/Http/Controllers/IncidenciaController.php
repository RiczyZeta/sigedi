<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidenciaRequest;
use App\Models\Dispositivo;
use App\Models\Incidencia;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Sprint 3 - Gestión de incidencias con el flujo de ITIL 4.
 * HU09 registro · HU10 prioridad · HU11 asignación · HU12 estados y cierre · HU16 consulta del solicitante.
 * Sprint 4: búsqueda con filtros (HU07) e historial desde la bitácora (HU13, registrada por IncidenciaObserver).
 */
class IncidenciaController extends Controller
{
    /**
     * HU16: el usuario solicitante solo ve las incidencias que él registró.
     * HU07 (Sprint 4): búsqueda por código de incidencia o de dispositivo, estado, prioridad y categoría.
     */
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $filtros = $request->only(['q', 'estado', 'prioridad', 'categoria']);

        $incidencias = Incidencia::with(['dispositivo', 'solicitante', 'tecnico'])
            ->when($usuario->tieneRol(Role::USUARIO), fn ($q) => $q->where('solicitante_id', $usuario->id))
            ->filtrar($filtros)
            ->ordenPrioridad()
            ->paginate(15)
            ->withQueryString();

        return view('incidencias.index', [
            'incidencias' => $incidencias,
            'filtros' => $filtros,
            'hayFiltros' => collect($filtros)->filter(fn ($v) => filled($v))->isNotEmpty(),
        ]);
    }

    public function create(): View
    {
        return view('incidencias.create', [
            'dispositivos' => Dispositivo::with('ubicacion')
                ->where('estado', '!=', 'de_baja')
                ->orderBy('codigo_patrimonial')
                ->get(),
        ]);
    }

    /** HU09 y HU10: registra la incidencia con código INC-AAAA-NNN y prioridad calculada. */
    public function store(IncidenciaRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $incidencia = Incidencia::create($datos + [
            'codigo' => Incidencia::generarCodigo(),
            'solicitante_id' => $request->user()->id,
            'prioridad' => Incidencia::calcularPrioridad($datos['impacto'], $datos['urgencia']),
            'estado' => 'registrada',
            'canal' => 'web',
        ]);

        return redirect()->route('incidencias.show', $incidencia)
            ->with('ok', "Incidencia {$incidencia->codigo} registrada. Guarda este número para consultar su avance.");
    }

    public function show(Request $request, Incidencia $incidencia): View
    {
        $usuario = $request->user();

        if ($usuario->tieneRol(Role::USUARIO) && (int) $incidencia->solicitante_id !== (int) $usuario->id) {
            abort(403, 'Solo puedes consultar las incidencias que registraste.');
        }

        $incidencia->load(['dispositivo.ubicacion', 'solicitante', 'tecnico']);

        // HU13 (Sprint 4): historial de la incidencia, visible para administrador y técnico.
        $historial = $usuario->puedeGestionarInventario()
            ? $incidencia->bitacoras()->with('usuario')->recientes()->get()
            : collect();

        $tecnicos = $usuario->esAdministrador()
            ? User::whereHas('role', fn ($q) => $q->where('nombre', Role::TECNICO))
                ->where('activo', true)->orderBy('name')->get()
            : collect();

        return view('incidencias.show', [
            'incidencia' => $incidencia,
            'puedeGestionar' => $this->puedeGestionar($usuario, $incidencia),
            'tecnicos' => $tecnicos,
            'historial' => $historial,
            // "asignada" solo se alcanza al asignar un técnico (HU11)
            'siguientes' => array_values(array_diff(Incidencia::TRANSICIONES[$incidencia->estado], ['asignada'])),
        ]);
    }

    /** HU11: solo el administrador asigna técnicos (la ruta lo protege). */
    public function asignar(Request $request, Incidencia $incidencia): RedirectResponse
    {
        $rolTecnico = Role::where('nombre', Role::TECNICO)->value('id');

        $datos = $request->validate([
            'tecnico_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('activo', true)->where('role_id', $rolTecnico)),
            ],
        ], [
            'tecnico_id.required' => 'Selecciona un técnico.',
            'tecnico_id.exists' => 'El usuario seleccionado no es un técnico activo.',
        ]);

        if (! in_array($incidencia->estado, ['registrada', 'asignada'], true)) {
            return back()->with('error', 'Solo se puede asignar una incidencia que aún no está en atención.');
        }

        $incidencia->update([
            'tecnico_id' => $datos['tecnico_id'],
            'estado' => 'asignada',
            'fecha_asignacion' => now(),
        ]);

        return back()->with('ok', 'Incidencia asignada correctamente.');
    }

    /** HU12: cambio de estado con transiciones válidas; no se resuelve ni se cierra sin solución. */
    public function estado(Request $request, Incidencia $incidencia): RedirectResponse
    {
        if (! $this->puedeGestionar($request->user(), $incidencia)) {
            abort(403, 'Solo el administrador o el técnico asignado pueden cambiar el estado.');
        }

        $datos = $request->validate([
            'estado' => ['required', Rule::in(['en_atencion', 'resuelta', 'cerrada'])],
            'solucion' => ['nullable', 'string', 'max:2000'],
        ], [
            'estado.required' => 'Selecciona el nuevo estado.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'solucion.max' => 'La solución no debe superar 2000 caracteres.',
        ]);

        $nuevo = $datos['estado'];

        if (! $incidencia->puedePasarA($nuevo)) {
            $destino = Incidencia::ETIQUETAS_ESTADO[$nuevo];

            return back()->with('error', "No se puede pasar de «{$incidencia->etiquetaEstado()}» a «{$destino}».");
        }

        $solucion = trim((string) ($datos['solucion'] ?? '')) ?: $incidencia->solucion;

        if (in_array($nuevo, ['resuelta', 'cerrada'], true) && blank($solucion)) {
            return back()->withErrors(['solucion' => 'Debes registrar la solución aplicada para continuar.'])->withInput();
        }

        $incidencia->update([
            'estado' => $nuevo,
            'solucion' => $solucion,
            'fecha_cierre' => $nuevo === 'cerrada' ? now() : null,
        ]);

        return back()->with('ok', "La incidencia pasó a «{$incidencia->etiquetaEstado()}».");
    }

    /** HU10: el administrador o técnico puede corregir el impacto y la urgencia; la prioridad se recalcula. */
    public function clasificar(Request $request, Incidencia $incidencia): RedirectResponse
    {
        $datos = $request->validate([
            'impacto' => ['required', Rule::in(array_keys(Incidencia::IMPACTOS))],
            'urgencia' => ['required', Rule::in(array_keys(Incidencia::URGENCIAS))],
        ], [
            'impacto.required' => 'Selecciona el impacto.',
            'impacto.in' => 'El impacto seleccionado no es válido.',
            'urgencia.required' => 'Selecciona la urgencia.',
            'urgencia.in' => 'La urgencia seleccionada no es válida.',
        ]);

        if ($incidencia->estado === 'cerrada') {
            return back()->with('error', 'No se puede reclasificar una incidencia cerrada.');
        }

        $incidencia->update($datos + [
            'prioridad' => Incidencia::calcularPrioridad($datos['impacto'], $datos['urgencia']),
        ]);

        return back()->with('ok', "Prioridad actualizada a «{$incidencia->etiquetaPrioridad()}».");
    }

    /** El administrador gestiona cualquier incidencia; el técnico, solo las que tiene asignadas. */
    private function puedeGestionar(User $usuario, Incidencia $incidencia): bool
    {
        return $usuario->esAdministrador()
            || ($usuario->tieneRol(Role::TECNICO) && (int) $incidencia->tecnico_id === (int) $usuario->id);
    }
}
