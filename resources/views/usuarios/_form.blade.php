{{-- Variables: $usuario (modelo o null), $roles --}}
<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Nombre completo</label>
        <input type="text" id="name" name="name" value="{{ old('name', $usuario?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Correo</label>
        <input type="email" id="email" name="email" value="{{ old('email', $usuario?->email) }}"
               class="form-control @error('email') is-invalid @enderror" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="role_id" class="form-label">Rol</label>
        <select id="role_id" name="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
            <option value="">Selecciona un rol</option>
            @foreach($roles as $rol)
                <option value="{{ $rol->id }}" @selected((int) old('role_id', $usuario?->role_id) === $rol->id)>
                    {{ ucfirst($rol->nombre) }}
                </option>
            @endforeach
        </select>
        @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="whatsapp" class="form-label">WhatsApp autorizado <span class="text-muted">(opcional)</span></label>
        <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $usuario?->whatsapp) }}"
               placeholder="+51987654321" class="form-control @error('whatsapp') is-invalid @enderror">
        @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="password" class="form-label">
            Contraseña @if($usuario)<span class="text-muted">(déjala vacía para no cambiarla)</span>@endif
        </label>
        <input type="password" id="password" name="password" autocomplete="new-password"
               class="form-control @error('password') is-invalid @enderror" @required(! $usuario)>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
        <input type="password" id="password_confirmation" name="password_confirmation"
               autocomplete="new-password" class="form-control" @required(! $usuario)>
    </div>
</div>
