{{-- Variables: $dispositivo (modelo o null), $ubicaciones, $responsables --}}
<div class="row g-3">
    <div class="col-md-4">
        <label for="codigo_patrimonial" class="form-label">Código patrimonial</label>
        <input type="text" id="codigo_patrimonial" name="codigo_patrimonial"
               value="{{ old('codigo_patrimonial', $dispositivo?->codigo_patrimonial) }}"
               class="form-control @error('codigo_patrimonial') is-invalid @enderror" required>
        @error('codigo_patrimonial')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="tipo" class="form-label">Tipo</label>
        <input type="text" id="tipo" name="tipo" list="tipos" value="{{ old('tipo', $dispositivo?->tipo) }}"
               class="form-control @error('tipo') is-invalid @enderror" required>
        <datalist id="tipos">
            @foreach(['Computadora', 'Laptop', 'Impresora', 'Proyector', 'Tablet', 'Router', 'Switch', 'Monitor'] as $tipo)
                <option value="{{ $tipo }}">
            @endforeach
        </datalist>
        @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="estado" class="form-label">Estado</label>
        <select id="estado" name="estado" class="form-select @error('estado') is-invalid @enderror" required>
            @foreach(\App\Models\Dispositivo::ETIQUETAS as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('estado', $dispositivo?->estado ?? 'operativo') === $valor)>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
        @error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="marca" class="form-label">Marca</label>
        <input type="text" id="marca" name="marca" value="{{ old('marca', $dispositivo?->marca) }}"
               class="form-control @error('marca') is-invalid @enderror">
        @error('marca')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="modelo" class="form-label">Modelo</label>
        <input type="text" id="modelo" name="modelo" value="{{ old('modelo', $dispositivo?->modelo) }}"
               class="form-control @error('modelo') is-invalid @enderror">
        @error('modelo')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="serie" class="form-label">Serie</label>
        <input type="text" id="serie" name="serie" value="{{ old('serie', $dispositivo?->serie) }}"
               class="form-control @error('serie') is-invalid @enderror">
        @error('serie')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="ubicacion_id" class="form-label">Ubicación</label>
        <select id="ubicacion_id" name="ubicacion_id" class="form-select @error('ubicacion_id') is-invalid @enderror" required>
            <option value="">Selecciona un ambiente</option>
            @foreach($ubicaciones as $ubicacion)
                <option value="{{ $ubicacion->id }}" @selected((int) old('ubicacion_id', $dispositivo?->ubicacion_id) === $ubicacion->id)>
                    {{ $ubicacion->nombre }}
                </option>
            @endforeach
        </select>
        @error('ubicacion_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="responsable_id" class="form-label">Responsable <span class="text-muted">(opcional)</span></label>
        <select id="responsable_id" name="responsable_id" class="form-select @error('responsable_id') is-invalid @enderror">
            <option value="">Sin responsable</option>
            @foreach($responsables as $responsable)
                <option value="{{ $responsable->id }}" @selected((int) old('responsable_id', $dispositivo?->responsable_id) === $responsable->id)>
                    {{ $responsable->name }}
                </option>
            @endforeach
        </select>
        @error('responsable_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="caracteristicas" class="form-label">Características técnicas <span class="text-muted">(opcional)</span></label>
        <textarea id="caracteristicas" name="caracteristicas" rows="3"
                  class="form-control @error('caracteristicas') is-invalid @enderror">{{ old('caracteristicas', $dispositivo?->caracteristicas) }}</textarea>
        @error('caracteristicas')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
