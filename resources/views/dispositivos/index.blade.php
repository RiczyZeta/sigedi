@extends('layouts.app')

@section('titulo', 'Dispositivos')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Dispositivos</h1>
        <div class="d-flex gap-2">
            @if(auth()->user()->esAdministrador())
                <a href="{{ route('dispositivos.importar') }}" class="btn btn-outline-primary">Importar desde Excel</a>
            @endif
            <a href="{{ route('dispositivos.create') }}" class="btn btn-primary">Nuevo dispositivo</a>
        </div>
    </div>

    @error('estado')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    {{-- HU07: búsqueda con filtros combinables --}}
    <form method="GET" action="{{ route('dispositivos.index') }}" class="card shadow-sm mb-3" role="search">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-4">
                <label for="q" class="form-label small mb-1">Código, serie, marca o modelo</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" id="q" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control"
                           placeholder="Ej.: PC-01" maxlength="100" autocomplete="off">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label for="tipo" class="form-label small mb-1">Tipo</label>
                <select id="tipo" name="tipo" class="form-select">
                    <option value="">Todos</option>
                    @foreach($tipos as $tipo)
                        <option value="{{ $tipo }}" @selected(($filtros['tipo'] ?? '') === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="ubicacion_id" class="form-label small mb-1">Ubicación</label>
                <select id="ubicacion_id" name="ubicacion_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach($ubicaciones as $u)
                        <option value="{{ $u->id }}" @selected((string) ($filtros['ubicacion_id'] ?? '') === (string) $u->id)>{{ $u->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="estado" class="form-label small mb-1">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos</option>
                    @foreach(\App\Models\Dispositivo::ETIQUETAS as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel-fill"></i> Buscar</button>
                @if($hayFiltros)
                    <a href="{{ route('dispositivos.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </div>
    </form>

    <p class="small text-muted mb-2">
        {{ $dispositivos->total() }} {{ $dispositivos->total() === 1 ? 'dispositivo encontrado' : 'dispositivos encontrados' }}
        @if($hayFiltros) con los filtros aplicados @endif
    </p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Marca / Modelo</th>
                    <th>Ubicación</th>
                    <th>Responsable</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                @forelse($dispositivos as $d)
                    <tr>
                        <td><a href="{{ route('dispositivos.show', $d) }}" class="fw-semibold text-decoration-none">{{ $d->codigo_patrimonial }}</a></td>
                        <td>{{ $d->tipo }}</td>
                        <td>{{ trim(($d->marca ?? '').' '.($d->modelo ?? '')) ?: '—' }}</td>
                        <td>{{ $d->ubicacion?->nombre ?? '—' }}</td>
                        <td>{{ $d->responsable?->name ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('dispositivos.estado', $d) }}" class="d-flex gap-1">
                                @csrf
                                @method('PATCH')
                                <select name="estado" class="form-select form-select-sm" aria-label="Estado de {{ $d->codigo_patrimonial }}">
                                    @foreach(\App\Models\Dispositivo::ETIQUETAS as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected($d->estado === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-{{ $d->colorEstado() }}">Cambiar</button>
                            </form>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('dispositivos.show', $d) }}" class="btn btn-sm btn-outline-secondary" title="Ficha e historial"><i class="bi bi-card-text"></i> Ficha</a>
                            <a href="{{ route('dispositivos.edit', $d) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron dispositivos con los criterios indicados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $dispositivos->links('pagination::bootstrap-5') }}
    </div>
@endsection
