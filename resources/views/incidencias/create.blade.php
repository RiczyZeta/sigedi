@extends('layouts.app')

@section('titulo', 'Reportar incidencia')

@section('contenido')
    <h1 class="h3 mb-3">Reportar incidencia</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('incidencias.store') }}" novalidate>
                @csrf
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="dispositivo_id" class="form-label">Dispositivo con la falla</label>
                        <select id="dispositivo_id" name="dispositivo_id"
                                class="form-select @error('dispositivo_id') is-invalid @enderror" required>
                            <option value="">Selecciona un dispositivo</option>
                            @foreach($dispositivos as $d)
                                <option value="{{ $d->id }}" @selected((int) old('dispositivo_id') === $d->id)>
                                    {{ $d->codigo_patrimonial }} — {{ $d->tipo }}@if($d->ubicacion) ({{ $d->ubicacion->nombre }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('dispositivo_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label for="categoria" class="form-label">Categoría</label>
                        <select id="categoria" name="categoria"
                                class="form-select @error('categoria') is-invalid @enderror" required>
                            <option value="">Selecciona</option>
                            @foreach(\App\Models\Incidencia::CATEGORIAS as $categoria)
                                <option value="{{ $categoria }}" @selected(old('categoria') === $categoria)>{{ $categoria }}</option>
                            @endforeach
                        </select>
                        @error('categoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción de la falla</label>
                        <textarea id="descripcion" name="descripcion" rows="4"
                                  class="form-control @error('descripcion') is-invalid @enderror"
                                  placeholder="¿Qué ocurre? ¿Desde cuándo? ¿Qué estabas haciendo?" required>{{ old('descripcion') }}</textarea>
                        @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="impacto" class="form-label">Impacto <span class="text-muted small">(¿a cuántas personas o clases afecta?)</span></label>
                        <select id="impacto" name="impacto" class="form-select @error('impacto') is-invalid @enderror" required>
                            <option value="">Selecciona</option>
                            @foreach(\App\Models\Incidencia::IMPACTOS as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(old('impacto') === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Alto: afecta a un aula o área completa · Medio: a varias personas · Bajo: a una sola persona.</div>
                        @error('impacto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="urgencia" class="form-label">Urgencia <span class="text-muted small">(¿qué tan pronto se necesita?)</span></label>
                        <select id="urgencia" name="urgencia" class="form-select @error('urgencia') is-invalid @enderror" required>
                            <option value="">Selecciona</option>
                            @foreach(\App\Models\Incidencia::URGENCIAS as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(old('urgencia') === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Alta: no se puede trabajar sin esto hoy · Media: esta semana · Baja: puede esperar.</div>
                        @error('urgencia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Registrar incidencia</button>
                    <a href="{{ route('incidencias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
