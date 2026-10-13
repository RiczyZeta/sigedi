@extends('layouts.app')

@section('titulo', 'Editar dispositivo')

@section('contenido')
    <h1 class="h3 mb-3">Editar dispositivo {{ $dispositivo->codigo_patrimonial }}</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('dispositivos.update', $dispositivo) }}" novalidate>
                @csrf
                @method('PUT')
                @include('dispositivos._form', ['dispositivo' => $dispositivo])
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ route('dispositivos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
