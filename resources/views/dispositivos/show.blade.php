@extends('layouts.app')

@section('titulo', 'Ficha '.$dispositivo->codigo_patrimonial)

@section('contenido')
    {{-- HU15: ficha del dispositivo con su historial de incidencias y actividades --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">
            {{ $dispositivo->codigo_patrimonial }}
            <span class="badge fs-6 bg-{{ $dispositivo->colorEstado() }}">{{ $dispositivo->etiquetaEstado() }}</span>
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('dispositivos.edit', $dispositivo) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-square"></i> Editar</a>
            <a href="{{ route('dispositivos.index') }}" class="btn btn-outline-secondary">Volver al listado</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Datos generales --}}
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted fw-bold mb-3">Datos generales</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Tipo</dt>
                        <dd class="col-sm-8">{{ $dispositivo->tipo }}</dd>

                        <dt class="col-sm-4">Marca / Modelo</dt>
                        <dd class="col-sm-8">{{ trim(($dispositivo->marca ?? '').' '.($dispositivo->modelo ?? '')) ?: '—' }}</dd>

                        <dt class="col-sm-4">N.° de serie</dt>
                        <dd class="col-sm-8">{{ $dispositivo->serie ?: '—' }}</dd>

                        <dt class="col-sm-4">Características</dt>
                        <dd class="col-sm-8">{!! $dispositivo->caracteristicas ? nl2br(e($dispositivo->caracteristicas)) : '—' !!}</dd>

                        <dt class="col-sm-4">Ubicación</dt>
                        <dd class="col-sm-8">{{ $dispositivo->ubicacion?->nombre ?? '—' }}</dd>

                        <dt class="col-sm-4">Responsable</dt>
                        <dd class="col-sm-8">{{ $dispositivo->responsable?->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Registrado</dt>
                        <dd class="col-sm-8">{{ $dispositivo->created_at?->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Resumen e historial --}}
        <div class="col-lg-5 d-grid gap-3 align-content-start">
            <div class="row g-2">
                <div class="col-4">
                    <div class="card text-center h-100"><div class="card-body p-2">
                        <div class="sg-stat-num">{{ $resumen['incidencias'] }}</div>
                        <div class="sg-stat-lbl">Incidencias</div>
                    </div></div>
                </div>
                <div class="col-4">
                    <div class="card text-center h-100"><div class="card-body p-2">
                        <div class="sg-stat-num text-warning-emphasis">{{ $resumen['abiertas'] }}</div>
                        <div class="sg-stat-lbl">Abiertas</div>
                    </div></div>
                </div>
                <div class="col-4">
                    <div class="card text-center h-100"><div class="card-body p-2">
                        <div class="sg-stat-num text-success-emphasis">{{ $resumen['mantenimientos'] }}</div>
                        <div class="sg-stat-lbl">Mantenimientos</div>
                    </div></div>
                </div>
            </div>
            @if($resumen['ultima'])
                <div class="small text-muted">
                    Última actividad: {{ \Illuminate\Support\Carbon::parse($resumen['ultima'])->format('d/m/Y H:i') }}
                </div>
            @endif

            {{-- HU14: registro manual de mantenimiento --}}
            @if($dispositivo->estado !== 'de_baja')
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h6"><i class="bi bi-tools"></i> Registrar mantenimiento</h2>
                        <form method="POST" action="{{ route('dispositivos.mantenimientos.store', $dispositivo) }}" novalidate>
                            @csrf
                            <div class="row g-2">
                                <div class="col-6">
                                    <label for="tipo_mantenimiento" class="form-label small mb-1">Tipo</label>
                                    <select id="tipo_mantenimiento" name="tipo_mantenimiento"
                                            class="form-select form-select-sm @error('tipo_mantenimiento') is-invalid @enderror" required>
                                        <option value="">Selecciona</option>
                                        <option value="preventivo" @selected(old('tipo_mantenimiento') === 'preventivo')>Preventivo</option>
                                        <option value="correctivo" @selected(old('tipo_mantenimiento') === 'correctivo')>Correctivo</option>
                                    </select>
                                    @error('tipo_mantenimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6">
                                    <label for="fecha" class="form-label small mb-1">Fecha</label>
                                    <input type="date" id="fecha" name="fecha" max="{{ now()->toDateString() }}"
                                           value="{{ old('fecha', now()->toDateString()) }}"
                                           class="form-control form-control-sm @error('fecha') is-invalid @enderror" required>
                                    @error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label for="descripcion" class="form-label small mb-1">Trabajo realizado</label>
                                    <textarea id="descripcion" name="descripcion" rows="3" maxlength="2000"
                                              class="form-control form-control-sm @error('descripcion') is-invalid @enderror"
                                              placeholder="Ej.: Limpieza interna, cambio de pasta térmica y actualización del antivirus." required>{{ old('descripcion') }}</textarea>
                                    @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-sm btn-primary">Guardar en la bitácora</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-secondary mb-0">El dispositivo está dado de baja: no admite nuevos mantenimientos.</div>
            @endif
        </div>
    </div>

    {{-- Incidencias del dispositivo --}}
    <div class="card shadow-sm mb-3" id="incidencias">
        <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Incidencias del dispositivo</h2></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Categoría</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Técnico</th>
                    <th>Registrada</th>
                </tr>
                </thead>
                <tbody>
                @forelse($incidencias as $i)
                    <tr>
                        <td><a href="{{ route('incidencias.show', $i) }}" class="fw-semibold text-decoration-none">{{ $i->codigo }}</a></td>
                        <td>{{ $i->categoria }}</td>
                        <td><span class="badge {{ $i->clasePrioridad() }}">{{ $i->etiquetaPrioridad() }}</span></td>
                        <td><span class="badge {{ $i->claseEstado() }}">{{ $i->etiquetaEstado() }}</span></td>
                        <td>{{ $i->tecnico?->name ?? 'Sin asignar' }}</td>
                        <td class="small">{{ $i->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">El dispositivo no registra incidencias.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($incidencias->hasPages())
            <div class="card-body pt-2 pb-0">{{ $incidencias->fragment('incidencias')->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>

    {{-- Bitácora del dispositivo --}}
    <div class="card shadow-sm" id="bitacora">
        <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-journal-text"></i> Bitácora de actividades</h2></div>
        <div class="card-body">
            @include('bitacora._linea', ['registros' => $bitacora, 'mostrarDispositivo' => false])
        </div>
        @if($bitacora->hasPages())
            <div class="card-body pt-0 pb-0">{{ $bitacora->fragment('bitacora')->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection
