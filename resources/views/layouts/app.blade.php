<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Panel') · SIGEDI</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/sigedi.css') }}?v=5" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-md navbar-dark sg-navbar">
    <div class="container">
        <a class="sg-brand" href="{{ route('panel') }}">
            <img src="{{ asset('img/insignia-256.png') }}" alt="Insignia de la I.E. INA 54">
            <span>
                <span class="sg-brand-name">SIGEDI</span>
                <span class="sg-brand-sub">I.E. INA 54 – Tambogrande</span>
            </span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
                aria-controls="menu" aria-expanded="false" aria-label="Mostrar menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-md-4 me-auto gap-md-1 mt-3 mt-md-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('panel') ? 'active' : '' }}" href="{{ route('panel') }}">
                        <i class="bi bi-grid-1x2-fill"></i> Panel</a>
                </li>
                @if(auth()->user()->puedeGestionarInventario())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dispositivos.*') ? 'active' : '' }}" href="{{ route('dispositivos.index') }}">
                            <i class="bi bi-pc-display"></i> Dispositivos</a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('incidencias.*') ? 'active' : '' }}" href="{{ route('incidencias.index') }}">
                        <i class="bi bi-exclamation-triangle-fill"></i> Incidencias</a>
                </li>
                @if(auth()->user()->puedeGestionarInventario())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('bitacora.*') ? 'active' : '' }}" href="{{ route('bitacora.index') }}">
                            <i class="bi bi-journal-text"></i> Bitácora</a>
                    </li>
                @endif
                @if(auth()->user()->esAdministrador())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">
                            <i class="bi bi-people-fill"></i> Usuarios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('asistente.*') ? 'active' : '' }}" href="{{ route('asistente.index') }}">
                            <i class="bi bi-whatsapp"></i> Asistente</a>
                    </li>
                @endif
            </ul>
            <div class="d-flex align-items-center gap-3 mt-3 mt-md-0">
                <div class="sg-user">
                    <div class="fw-semibold">{{ auth()->user()->name }}</div>
                    <span class="sg-role">{{ auth()->user()->role->nombre }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</button>
                </form>
            </div>
        </div>
    </div>
</nav>
<div class="sg-stripe"></div>

<main class="container py-4">
    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @yield('contenido')
</main>

<footer class="text-center sg-footer py-4">
    <img src="{{ asset('img/insignia-256.png') }}" alt=""> SIGEDI · I.E. INA 54 – Tambogrande · 2026
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
