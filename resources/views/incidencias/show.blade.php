@extends('layouts.app')

@section('titulo', $incidencia->codigo)

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">
            {{ $incidencia->codigo }}
            <span class="badge fs-6 {{ $incidencia->claseEstado() }}">{{ $incidencia->etiquetaEstado() }}</span>
            <span class="badge fs-6 {{ $incidencia->clasePrioridad() }}">Prioridad {{ $incidencia->etiquetaPrioridad() }}</span>
        </h1>
        <a href="{{ route('incidencias.index') }}" class="btn btn-outline-secondary">Volver al listado</a>
    </div>

    <div class="row g-3">
        {{-- Datos de la incidencia --}}
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Dispositivo</dt>
                        <dd class="col-sm-8">
                            @if(auth()->user()->puedeGestionarInventario())
                                <a href="{{ route('dispositivos.show', $incidencia->dispositivo) }}" class="text-decoration-none">{{ $incidencia->dispositivo->codigo_patrimonial }}</a>
                            @else
                                {{ $incidencia->dispositivo->codigo_patrimonial }}
                            @endif
                            — {{ $incidencia->dispositivo->tipo }}
                            @if($incidencia->dispositivo->ubicacion)
                                <span class="text-muted">({{ $incidencia->dispositivo->ubicacion->nombre }})</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Categoría</dt>
                        <dd class="col-sm-8">{{ $incidencia->categoria }}</dd>

                        <dt class="col-sm-4">Descripción</dt>
                        <dd class="col-sm-8">{!! nl2br(e($incidencia->descripcion)) !!}</dd>

                        <dt class="col-sm-4">Impacto / Urgencia</dt>
                        <dd class="col-sm-8">
                            {{ \App\Models\Incidencia::IMPACTOS[$incidencia->impacto] ?? $incidencia->impacto }} /
                            {{ \App\Models\Incidencia::URGENCIAS[$incidencia->urgencia] ?? $incidencia->urgencia }}
                        </dd>

                        <dt class="col-sm-4">Solicitante</dt>
                        <dd class="col-sm-8">{{ $incidencia->solicitante->name }} <span class="text-muted small">(canal: {{ $incidencia->canal }})</span></dd>

                        <dt class="col-sm-4">Registrada</dt>
                        <dd class="col-sm-8">{{ $incidencia->created_at->format('d/m/Y H:i') }}</dd>

                        <dt class="col-sm-4">Técnico asignado</dt>
                        <dd class="col-sm-8">
                            {{ $incidencia->tecnico?->name ?? 'Sin asignar' }}
                            @if($incidencia->fecha_asignacion)
                                <span class="text-muted small">(desde {{ $incidencia->fecha_asignacion->format('d/m/Y H:i') }})</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Solución aplicada</dt>
                        <dd class="col-sm-8">{!! $incidencia->solucion ? nl2br(e($incidencia->solucion)) : '<span class="text-muted">Aún sin solución registrada.</span>' !!}</dd>

                        @if($incidencia->fecha_cierre)
                            <dt class="col-sm-4">Cerrada</dt>
                            <dd class="col-sm-8">{{ $incidencia->fecha_cierre->format('d/m/Y H:i') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Acciones según el rol --}}
        <div class="col-lg-5 d-grid gap-3 align-content-start">
            @if(auth()->user()->esAdministrador() && in_array($incidencia->estado, ['registrada', 'asignada'], true))
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h6">Asignar técnico</h2>
                        <form method="POST" action="{{ route('incidencias.asignar', $incidencia) }}" class="d-flex gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="tecnico_id" class="form-select @error('tecnico_id') is-invalid @enderror" aria-label="Técnico" required>
                                <option value="">Selecciona un técnico</option>
                                @foreach($tecnicos as $tecnico)
                                    <option value="{{ $tecnico->id }}" @selected((int) $incidencia->tecnico_id === $tecnico->id)>{{ $tecnico->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary">Asignar</button>
                        </form>
                        @error('tecnico_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @if($tecnicos->isEmpty())
                            <div class="form-text">No hay técnicos activos. Crea uno en el módulo Usuarios.</div>
                        @endif
                    </div>
                </div>
            @endif

            @if($puedeGestionar && count($siguientes) > 0)
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h6">Cambiar estado</h2>
                        <form method="POST" action="{{ route('incidencias.estado', $incidencia) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-2">
                                <label for="estado" class="form-label small mb-1">Nuevo estado</label>
                                <select id="estado" name="estado" class="form-select" required>
                                    @foreach($siguientes as $estado)
                                        <option value="{{ $estado }}" @selected(old('estado') === $estado)>{{ \App\Models\Incidencia::ETIQUETAS_ESTADO[$estado] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label for="solucion" class="form-label small mb-1">Solución aplicada <span class="text-muted">(obligatoria para resolver o cerrar)</span></label>
                                <textarea id="solucion" name="solucion" rows="3"
                                          class="form-control @error('solucion') is-invalid @enderror">{{ old('solucion', $incidencia->solucion) }}</textarea>
                                @error('solucion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Guardar estado</button>
                        </form>
                    </div>
                </div>
            @elseif(auth()->user()->tieneRol(\App\Models\Role::TECNICO) && ! $puedeGestionar)
                <div class="alert alert-secondary mb-0">Solo el técnico asignado o el administrador pueden cambiar el estado.</div>
            @endif

            @if(auth()->user()->puedeGestionarInventario() && $incidencia->estado !== 'cerrada')
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h6">Reclasificar prioridad</h2>
                        <form method="POST" action="{{ route('incidencias.clasificar', $incidencia) }}" class="row g-2">
                            @csrf
                            @method('PATCH')
                            <div class="col-6">
                                <select name="impacto" class="form-select form-select-sm" aria-label="Impacto">
                                    @foreach(\App\Models\Incidencia::IMPACTOS as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected($incidencia->impacto === $valor)>Impacto {{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="urgencia" class="form-select form-select-sm" aria-label="Urgencia">
                                    @foreach(\App\Models\Incidencia::URGENCIAS as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected($incidencia->urgencia === $valor)>Urgencia {{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-outline-primary">Recalcular prioridad</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- HU13: historial de la incidencia registrado en la bitácora --}}
    @if(auth()->user()->puedeGestionarInventario())
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-journal-text"></i> Historial de la incidencia</h2></div>
            <div class="card-body">
                @include('bitacora._linea', ['registros' => $historial, 'mostrarDispositivo' => false])
            </div>
        </div>
    @endif
@endsection
