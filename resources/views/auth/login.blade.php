<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · SIGEDI</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/sigedi.css') }}?v=4" rel="stylesheet">
</head>
<body>
<div class="sg-login">
    <section class="sg-login-hero" aria-hidden="true">
        <img src="{{ asset('img/insignia.png') }}" alt="Insignia de la I.E. INA 54">
        <div class="sg-login-title">SIGEDI</div>
        <div class="sg-login-tag">Gestión de dispositivos e incidencias</div>
        <div class="sg-login-school">I.E. INA 54 – Tambogrande</div>
    </section>

    <section class="sg-login-form">
        <div class="sg-login-box">
            <div class="sg-login-mobile">
                <img src="{{ asset('img/insignia-256.png') }}" alt="Insignia de la I.E. INA 54">
                <div class="sg-login-title">SIGEDI</div>
                <div class="text-muted small">Gestión de dispositivos e incidencias<br>I.E. INA 54 – Tambogrande</div>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <h2 class="h4 fw-bold mb-1">Iniciar sesión</h2>
                    <p class="text-muted small mb-4">Ingresa con tu correo institucional.</p>

                    <form method="POST" action="{{ route('login') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Correo</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                   class="form-control @error('email') is-invalid @enderror"
                                   autocomplete="username" autofocus required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   autocomplete="current-password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                            <label class="form-check-label" for="remember">Mantener la sesión iniciada</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2"><i class="bi bi-box-arrow-in-right"></i> Ingresar</button>
                    </form>
                </div>
            </div>
            <p class="text-center text-muted small mt-3 mb-0">© 2026 SIGEDI · I.E. INA 54 – Tambogrande</p>
        </div>
    </section>
</div>
</body>
</html>
