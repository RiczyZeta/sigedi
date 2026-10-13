<?php

namespace App\Observers;

use App\Models\Bitacora;
use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Sprint 4 - HU13: registra en la bitácora la creación, la asignación, cada cambio de estado
 * y la reclasificación de las incidencias. La incidencia se vincula también a su dispositivo,
 * para que la acción aparezca en la ficha del equipo (HU15).
 */
class IncidenciaObserver
{
    public function created(Incidencia $incidencia): void
    {
        Bitacora::registrar(
            'registro_incidencia',
            "Se registró la incidencia {$incidencia->codigo} ({$incidencia->categoria}, prioridad {$incidencia->etiquetaPrioridad()}) por el canal ".($incidencia->canal ?? 'web').'.',
            incidencia: $incidencia,
        );
    }

    public function updated(Incidencia $incidencia): void
    {
        $cambios = $incidencia->getChanges();
        $asignada = array_key_exists('tecnico_id', $cambios) && $incidencia->tecnico_id;

        if ($asignada) {
            $tecnico = $this->nombreUsuario($incidencia->tecnico_id);
            $anterior = $incidencia->getOriginal('tecnico_id');
            $texto = $anterior
                ? "Se reasignó la incidencia {$incidencia->codigo} de {$this->nombreUsuario($anterior)} a {$tecnico}."
                : "Se asignó la incidencia {$incidencia->codigo} al técnico {$tecnico}.";

            Bitacora::registrar('asignacion', $texto, incidencia: $incidencia);
        }

        // El paso a «Asignada» ya queda descrito por el registro de asignación.
        if (array_key_exists('estado', $cambios) && ! ($asignada && $incidencia->estado === 'asignada')) {
            $this->registrarCambioDeEstado($incidencia);
        }

        if (array_key_exists('prioridad', $cambios) || array_key_exists('impacto', $cambios) || array_key_exists('urgencia', $cambios)) {
            $anterior = Incidencia::PRIORIDADES[$incidencia->getOriginal('prioridad')] ?? $incidencia->getOriginal('prioridad');
            Bitacora::registrar(
                'reclasificacion',
                "Se reclasificó la incidencia {$incidencia->codigo}: impacto ".Incidencia::IMPACTOS[$incidencia->impacto]
                    .', urgencia '.Incidencia::URGENCIAS[$incidencia->urgencia]
                    ." (prioridad «{$anterior}» → «{$incidencia->etiquetaPrioridad()}»).",
                incidencia: $incidencia,
            );
        }
    }

    private function registrarCambioDeEstado(Incidencia $incidencia): void
    {
        $anterior = $incidencia->getOriginal('estado');
        $nuevo = $incidencia->estado;
        $etiquetaAnterior = Incidencia::ETIQUETAS_ESTADO[$anterior] ?? $anterior;
        $solucion = Str::limit((string) $incidencia->solucion, 300);

        [$accion, $texto] = match (true) {
            $anterior === 'resuelta' && $nuevo === 'en_atencion' => [
                'reapertura',
                "Se reabrió la incidencia {$incidencia->codigo} porque la solución no resolvió la falla.",
            ],
            $nuevo === 'resuelta' => [
                'resolucion',
                "La incidencia {$incidencia->codigo} pasó a «Resuelta». Solución: {$solucion}",
            ],
            $nuevo === 'cerrada' => [
                'cierre',
                "Se cerró la incidencia {$incidencia->codigo}. Solución aplicada: {$solucion}",
            ],
            default => [
                'cambio_estado_incidencia',
                "La incidencia {$incidencia->codigo} pasó de «{$etiquetaAnterior}» a «{$incidencia->etiquetaEstado()}».",
            ],
        };

        Bitacora::registrar($accion, $texto, incidencia: $incidencia);
    }

    private function nombreUsuario(?int $id): string
    {
        return $id ? (User::whereKey($id)->value('name') ?? 'Usuario eliminado') : 'Sin asignar';
    }
}
