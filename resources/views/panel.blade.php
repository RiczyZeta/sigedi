@extends('layouts.app')

@section('titulo', 'Panel')

@section('contenido')
    {{-- Encabezado de bienvenida --}}
    <div class="sg-hero mb-4 d-flex align-items-center gap-4">
        <img src="{{ asset('img/insignia-256.png') }}" alt="Insignia de la I.E. INA 54" class="d-none d-sm-block">
        <div>
            <h1 class="h3 mb-1">Bienvenido, {{ auth()->user()->name }}</h1>
            <p class="mb-2 opacity-75">I.E. INA 54 – Tambogrande · Gestión de dispositivos e incidencias</p>
            <span class="sg-role">{{ auth()->user()->role->nombre }}</span>
        </div>
    </div>

    {{-- Contadores --}}
    <div class="row g-3 mb-4">
        @foreach($stats as $s)
            <div class="col-6 col-lg">
                <div class="card h-100">
                    <div class="sg-stat">
                        <div class="sg-stat-icon bg-{{ $s['color'] }}-subtle text-{{ $s['color'] }}-emphasis"><i class="bi {{ $s['icono'] }}"></i></div>
                        <div>
                            <div class="sg-stat-num">{{ $s['valor'] }}</div>
                            <div class="sg-stat-lbl">{{ $s['etiqueta'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Módulos --}}
    <h2 class="h6 text-uppercase text-muted fw-bold mb-3" style="letter-spacing:.06em">Módulos</h2>
    <div class="row g-3">
        @if(auth()->user()->esAdministrador())
            <div class="col-md-4">
                <a href="{{ route('usuarios.index') }}" class="card sg-mod h-100 text-decoration-none">
                    <div class="card-body">
                        <i class="bi bi-people-fill sg-mod-icon"></i>
                        <h3 class="h5 text-primary mt-2">Usuarios</h3>
                        <p class="text-muted small mb-0">Crear usuarios, asignar roles y activarlos o desactivarlos.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('asistente.index') }}" class="card sg-mod h-100 text-decoration-none">
                    <div class="card-body">
                        <i class="bi bi-whatsapp sg-mod-icon"></i>
                        <h3 class="h5 text-primary mt-2">Asistente WhatsApp</h3>
                        <p class="text-muted small mb-0">Estado de la conexión con Meta y Gemini, indicadores de uso y últimos mensajes.</p>
                    </div>
                </a>
            </div>
        @endif

        <div class="col-md-4">
            @if(auth()->user()->puedeGestionarInventario())
                <a href="{{ route('dispositivos.index') }}" class="card sg-mod is-yellow h-100 text-decoration-none">
                    <div class="card-body">
                        <i class="bi bi-pc-display sg-mod-icon"></i>
                        <h3 class="h5 text-primary mt-2">Dispositivos</h3>
                        <p class="text-muted small mb-0">Inventario del área de Cómputo/TI: características, ubicación, responsable y estado.</p>
                    </div>
                </a>
            @else
                <div class="card h-100 bg-body-secondary">
                    <div class="card-body">
                        <i class="bi bi-pc-display sg-mod-icon text-secondary"></i>
                        <h3 class="h5 text-secondary mt-2">Dispositivos</h3>
                        <p class="text-muted small mb-0">Disponible para administradores y técnicos.</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <a href="{{ route('incidencias.index') }}" class="card sg-mod is-red h-100 text-decoration-none">
                <div class="card-body">
                    <i class="bi bi-exclamation-triangle-fill sg-mod-icon"></i>
                    <h3 class="h5 text-primary mt-2">Incidencias</h3>
                    <p class="text-muted small mb-0">
                        @if(auth()->user()->tieneRol(\App\Models\Role::USUARIO))
                            Reporta una falla y consulta el estado de tus incidencias.
                        @else
                            Registro, prioridad, asignación y cierre con el flujo de ITIL 4.
                        @endif
                    </p>
                </div>
            </a>
        </div>

        @if(auth()->user()->puedeGestionarInventario())
            <div class="col-md-4">
                <a href="{{ route('bitacora.index') }}" class="card sg-mod h-100 text-decoration-none">
                    <div class="card-body">
                        <i class="bi bi-journal-text sg-mod-icon"></i>
                        <h3 class="h5 text-primary mt-2">Bitácora</h3>
                        <p class="text-muted small mb-0">Historial de acciones y mantenimientos de cada dispositivo.</p>
                    </div>
                </a>
            </div>
        @endif
    </div>
@endsection
