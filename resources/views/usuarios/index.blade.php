@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Usuarios</h1>
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary">Nuevo usuario</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>WhatsApp</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                @forelse($usuarios as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td class="text-capitalize">{{ $u->role->nombre }}</td>
                        <td>{{ $u->whatsapp ?? '—' }}</td>
                        <td>
                            @if($u->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('usuarios.edit', $u) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            @if(! $u->is(auth()->user()))
                                <form method="POST" action="{{ route('usuarios.estado', $u) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm {{ $u->activo ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                        {{ $u->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay usuarios registrados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $usuarios->links('pagination::bootstrap-5') }}
    </div>
@endsection
