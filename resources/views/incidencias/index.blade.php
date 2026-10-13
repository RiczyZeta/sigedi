@extends('layouts.app')

@section('titulo', 'Incidencias')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">
            {{ auth()->user()->tieneRol(\App\Models\Role::USUARIO) ? 'Mis incidencias' : 'Incidencias' }}
        </h1>
        <a href="{{ route('incidencias.create') }}" class="btn btn-primary">Reportar incidencia</a>
    </div>

    {{-- HU07: búsqueda con filtros combinables --}}
    <form method="GET" action="{{ route('incidencias.index') }}" class="card shadow-sm mb-3" role="search">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-4">
                <label for="q" class="form-label small mb-1">Código de incidencia o de dispositivo</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" id="q" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control"
                           placeholder="Ej.: INC-2026-0 o PC-01" maxlength="100" autocomplete="off">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label for="estado" class="form-label small mb-1">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos</option>
                    @foreach(\App\Models\Incidencia::ETIQUETAS_ESTADO as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="prioridad" class="form-label small mb-1">Prioridad</label>
                <select id="prioridad" name="prioridad" class="form-select">
                    <option value="">Todas</option>
                    @foreach(\App\Models\Incidencia::PRIORIDADES as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(($filtros['prioridad'] ?? '') === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="categoria" class="form-label small mb-1">Categoría</label>
                <select id="categoria" name="categoria" class="form-select">
                    <option value="">Todas</option>
                    @foreach(\App\Models\Incidencia::CATEGORIAS as $categoria)
                        <option value="{{ $categoria }}" @selected(($filtros['categoria'] ?? '') === $categoria)>{{ $categoria }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel-fill"></i> Buscar</button>
                @if($hayFiltros)
                    <a href="{{ route('incidencias.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </div>
    </form>

    <p class="small text-muted mb-2">
        {{ $incidencias->total() }} {{ $incidencias->total() === 1 ? 'incidencia encontrada' : 'incidencias encontradas' }}
        @if($hayFiltros) con los filtros aplicados @endif
    </p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Dispositivo</th>
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
                        <td>{{ $i->dispositivo->codigo_patrimonial }} <span class="text-muted small">{{ $i->dispositivo->tipo }}</span></td>
                        <td>{{ $i->categoria }}</td>
                        <td><span class="badge {{ $i->clasePrioridad() }}">{{ $i->etiquetaPrioridad() }}</span></td>
                        <td><span class="badge {{ $i->claseEstado() }}">{{ $i->etiquetaEstado() }}</span></td>
                        <td>{{ $i->tecnico?->name ?? 'Sin asignar' }}</td>
                        <td class="small">{{ $i->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron incidencias con los criterios indicados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $incidencias->links('pagination::bootstrap-5') }}
    </div>
@endsection
