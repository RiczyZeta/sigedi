@extends('layouts.app')

@section('titulo', 'Bitácora')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Bitácora digital de actividades</h1>
            <p class="text-muted small mb-0">Registro inalterable de las acciones sobre dispositivos e incidencias y de los mantenimientos realizados.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('bitacora.index') }}" class="card shadow-sm mb-3" role="search">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-4">
                <label for="q" class="form-label small mb-1">Dispositivo, incidencia o texto</label>
                <input type="search" id="q" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control"
                       placeholder="Ej.: PC-01 o INC-2026" maxlength="100">
            </div>
            <div class="col-6 col-md-2">
                <label for="tipo" class="form-label small mb-1">Tipo</label>
                <select id="tipo" name="tipo" class="form-select">
                    <option value="">Todos</option>
                    @foreach(\App\Models\Bitacora::TIPOS as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(($filtros['tipo'] ?? '') === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label for="accion" class="form-label small mb-1">Acción</label>
                <select id="accion" name="accion" class="form-select">
                    <option value="">Todas</option>
                    @foreach(\App\Models\Bitacora::ACCIONES as $valor => $info)
                        <option value="{{ $valor }}" @selected(($filtros['accion'] ?? '') === $valor)>{{ $info[0] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label for="desde" class="form-label small mb-1">Desde</label>
                <input type="date" id="desde" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="form-control">
            </div>
            <div class="col-6 col-md-3">
                <label for="hasta" class="form-label small mb-1">Hasta</label>
                <input type="date" id="hasta" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                @if($hayFiltros)
                    <a href="{{ route('bitacora.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </div>
    </form>

    <p class="small text-muted mb-2">{{ $registros->total() }} {{ $registros->total() === 1 ? 'registro' : 'registros' }}</p>

    <div class="card shadow-sm">
        <div class="card-body">
            @include('bitacora._linea', ['registros' => $registros, 'mostrarDispositivo' => true])
        </div>
    </div>

    <div class="mt-3">
        {{ $registros->links('pagination::bootstrap-5') }}
    </div>
@endsection
