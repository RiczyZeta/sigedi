@extends('layouts.app')

@section('titulo', 'Nuevo dispositivo')

@section('contenido')
    <h1 class="h3 mb-3">Nuevo dispositivo</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('dispositivos.store') }}" novalidate>
                @csrf
                @include('dispositivos._form', ['dispositivo' => null])
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('dispositivos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
