@extends('layouts.app')

@section('titulo', 'Nuevo usuario')

@section('contenido')
    <h1 class="h3 mb-3">Nuevo usuario</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('usuarios.store') }}" novalidate>
                @csrf
                @include('usuarios._form', ['usuario' => null])
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
