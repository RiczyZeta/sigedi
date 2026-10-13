<?php

namespace App\Observers;

use App\Models\Bitacora;
use App\Models\Dispositivo;
use App\Models\Ubicacion;
use App\Models\User;

/**
 * Sprint 4 - HU13: registra en la bitácora toda acción relevante sobre los dispositivos.
 * Se ejecuta para cualquier origen del cambio (formulario, cambio rápido de estado o importación desde Excel).
 */
class DispositivoObserver
{
    /** Campos que, si cambian, se registran como "actualización de datos". */
    private const CAMPOS_GENERALES = [
        'codigo_patrimonial' => 'código patrimonial',
        'tipo' => 'tipo',
        'marca' => 'marca',
        'modelo' => 'modelo',
        'serie' => 'serie',
        'caracteristicas' => 'características',
    ];

    public function created(Dispositivo $dispositivo): void
    {
        // Si no se envió el estado, la base de datos asigna «operativo» por defecto.
        $estado = Dispositivo::ETIQUETAS[$dispositivo->estado ?? 'operativo'] ?? $dispositivo->estado;

        Bitacora::registrar(
            'registro_dispositivo',
            "Se registró el dispositivo {$dispositivo->codigo_patrimonial} ({$dispositivo->tipo}) con estado «{$estado}».",
            dispositivo: $dispositivo,
        );
    }

    public function updated(Dispositivo $dispositivo): void
    {
        $cambios = $dispositivo->getChanges();

        if (array_key_exists('estado', $cambios)) {
            $anterior = Dispositivo::ETIQUETAS[$dispositivo->getOriginal('estado')] ?? $dispositivo->getOriginal('estado');
            Bitacora::registrar(
                'cambio_estado_dispositivo',
                "Estado de {$dispositivo->codigo_patrimonial}: «{$anterior}» → «{$dispositivo->etiquetaEstado()}».",
                dispositivo: $dispositivo,
            );
        }

        if (array_key_exists('ubicacion_id', $cambios)) {
            $anterior = $this->nombreUbicacion($dispositivo->getOriginal('ubicacion_id'));
            $nueva = $this->nombreUbicacion($dispositivo->ubicacion_id);
            Bitacora::registrar(
                'cambio_ubicacion',
                "Ubicación de {$dispositivo->codigo_patrimonial}: «{$anterior}» → «{$nueva}».",
                dispositivo: $dispositivo,
            );
        }

        if (array_key_exists('responsable_id', $cambios)) {
            $anterior = $this->nombreUsuario($dispositivo->getOriginal('responsable_id'));
            $nuevo = $this->nombreUsuario($dispositivo->responsable_id);
            Bitacora::registrar(
                'cambio_responsable',
                "Responsable de {$dispositivo->codigo_patrimonial}: «{$anterior}» → «{$nuevo}».",
                dispositivo: $dispositivo,
            );
        }

        $generales = array_values(array_intersect_key(self::CAMPOS_GENERALES, $cambios));

        if ($generales) {
            Bitacora::registrar(
                'actualizacion_dispositivo',
                "Se actualizaron los datos de {$dispositivo->codigo_patrimonial}: ".implode(', ', $generales).'.',
                dispositivo: $dispositivo,
            );
        }
    }

    private function nombreUbicacion(?int $id): string
    {
        return $id ? (Ubicacion::whereKey($id)->value('nombre') ?? 'Sin ubicación') : 'Sin ubicación';
    }

    private function nombreUsuario(?int $id): string
    {
        return $id ? (User::whereKey($id)->value('name') ?? 'Sin responsable') : 'Sin responsable';
    }
}
