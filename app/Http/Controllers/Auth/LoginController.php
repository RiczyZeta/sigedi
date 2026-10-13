<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** HU01 - Inicio de sesión con credenciales válidas; error con credenciales inválidas. */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Ingresa tu correo.',
            'email.email' => 'Ingresa un correo válido.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);

        // Límite de 5 intentos fallidos por correo + IP (RNF01)
        $key = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $segundos = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Demasiados intentos. Intenta nuevamente en {$segundos} segundos.",
            ]);
        }

        // Solo ingresan usuarios activos
        $intento = Auth::attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password'], 'activo' => true],
            $request->boolean('remember')
        );

        if (! $intento) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son válidas o el usuario está inactivo.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('panel'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
