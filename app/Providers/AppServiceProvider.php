<?php

namespace App\Providers;

use App\Models\Dispositivo;
use App\Models\Incidencia;
use App\Observers\DispositivoObserver;
use App\Observers\IncidenciaObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sprint 4 - HU13: bitácora automática de dispositivos e incidencias.
        Dispositivo::observe(DispositivoObserver::class);
        Incidencia::observe(IncidenciaObserver::class);
    }
}
