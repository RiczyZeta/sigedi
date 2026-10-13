<?php

use App\Http\Controllers\AsistenteController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispositivoController;
use App\Http\Controllers\ImportacionDispositivoController;
use App\Http\Controllers\IncidenciaController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookWhatsAppController;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\VerificarFirmaWhatsApp;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');

// Webhook de WhatsApp Business Cloud API (Sprint 5, HU17). Sin sesión de usuario: Meta lo llama directamente.
// La verificación usa el token de .env y los mensajes se aceptan solo con la firma de Meta (RNF02).
Route::get('webhook/whatsapp', [WebhookWhatsAppController::class, 'verificar'])->name('whatsapp.verificar');
Route::post('webhook/whatsapp', [WebhookWhatsAppController::class, 'recibir'])
    ->middleware([VerificarFirmaWhatsApp::class, 'throttle:120,1'])
    ->name('whatsapp.recibir');

// Visitantes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

// Usuarios autenticados y activos
Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/panel', [DashboardController::class, 'index'])->name('panel');

    // Todos los roles: registrar y consultar incidencias (Sprint 3)
    Route::get('incidencias', [IncidenciaController::class, 'index'])->name('incidencias.index');
    Route::get('incidencias/create', [IncidenciaController::class, 'create'])->name('incidencias.create');
    Route::post('incidencias', [IncidenciaController::class, 'store'])->name('incidencias.store');
    Route::get('incidencias/{incidencia}', [IncidenciaController::class, 'show'])->name('incidencias.show');

    // Administrador y técnico: inventario (Sprint 2), clasificación y estados (Sprint 3),
    // búsqueda, ficha del dispositivo y bitácora (Sprint 4)
    Route::middleware(RoleMiddleware::class.':'.Role::ADMINISTRADOR.','.Role::TECNICO)->group(function () {
        Route::resource('dispositivos', DispositivoController::class)
            ->parameters(['dispositivos' => 'dispositivo'])
            ->except(['destroy']);
        Route::patch('dispositivos/{dispositivo}/estado', [DispositivoController::class, 'estado'])
            ->name('dispositivos.estado');
        Route::post('dispositivos/{dispositivo}/mantenimientos', [MantenimientoController::class, 'store'])
            ->name('dispositivos.mantenimientos.store');

        // Bitácora digital: solo lectura (HU13: los registros no se editan ni se eliminan)
        Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');

        Route::patch('incidencias/{incidencia}/clasificar', [IncidenciaController::class, 'clasificar'])
            ->name('incidencias.clasificar');
        Route::patch('incidencias/{incidencia}/estado', [IncidenciaController::class, 'estado'])
            ->name('incidencias.estado');
    });

    // Solo administrador
    Route::middleware(RoleMiddleware::class.':'.Role::ADMINISTRADOR)->group(function () {
        // Usuarios (Sprint 1)
        Route::resource('usuarios', UserController::class)
            ->parameters(['usuarios' => 'usuario'])
            ->except(['show', 'destroy']);
        Route::patch('usuarios/{usuario}/estado', [UserController::class, 'estado'])->name('usuarios.estado');

        // Importación del inventario desde Excel (Sprint 2)
        Route::get('importar-dispositivos', [ImportacionDispositivoController::class, 'create'])
            ->name('dispositivos.importar');
        Route::post('importar-dispositivos', [ImportacionDispositivoController::class, 'store'])
            ->name('dispositivos.importar.store');
        Route::get('importar-dispositivos/plantilla', [ImportacionDispositivoController::class, 'plantilla'])
            ->name('dispositivos.plantilla');

        // Panel del asistente de WhatsApp (Sprint 5)
        Route::get('asistente', [AsistenteController::class, 'index'])->name('asistente.index');

        // Asignación de técnicos (Sprint 3)
        Route::patch('incidencias/{incidencia}/asignar', [IncidenciaController::class, 'asignar'])
            ->name('incidencias.asignar');
    });
});
