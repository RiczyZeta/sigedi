@extends('layouts.app')

@section('titulo', 'Importar dispositivos')

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Importar dispositivos desde Excel</h1>
        <a href="{{ route('dispositivos.index') }}" class="btn btn-outline-secondary">Volver al listado</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <ol class="mb-3">
                <li>Descarga la plantilla y completa tus equipos desde la fila 2 (no cambies los encabezados).</li>
                <li>La <strong>ubicación</strong> debe coincidir con un ambiente ya registrado y el
                    <strong>responsable</strong> debe ser el correo de un usuario ya registrado.</li>
                <li>Sube el archivo .xlsx. Las filas con errores se informan y las filas válidas se importan igual.</li>
            </ol>

            <a href="{{ route('dispositivos.plantilla') }}" class="btn btn-outline-primary mb-4">Descargar plantilla</a>

            <form method="POST" action="{{ route('dispositivos.importar.store') }}" enctype="multipart/form-data" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="archivo" class="form-label">Archivo Excel (.xlsx, máximo 2 MB)</label>
                    <input type="file" id="archivo" name="archivo" accept=".xlsx"
                           class="form-control @error('archivo') is-invalid @enderror" required>
                    @error('archivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary">Importar</button>
            </form>
        </div>
    </div>

    @if(session('resultado'))
        @php($resultado = session('resultado'))
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Resultado de la importación</h2>
                <p class="mb-2">
                    Filas procesadas: <strong>{{ $resultado['total'] }}</strong> ·
                    Importadas: <strong class="text-success">{{ $resultado['creados'] }}</strong> ·
                    Con errores: <strong class="text-danger">{{ count($resultado['errores']) }}</strong>
                </p>

                @if(count($resultado['errores']) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                            <tr><th style="width: 90px;">Fila</th><th>Motivo</th></tr>
                            </thead>
                            <tbody>
                            @foreach($resultado['errores'] as $error)
                                <tr>
                                    <td>{{ $error['fila'] }}</td>
                                    <td>{{ $error['mensaje'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Corrige esas filas en tu archivo y vuelve a importarlas: las ya importadas no se duplican.</p>
                @endif
            </div>
        </div>
    @endif
@endsection
