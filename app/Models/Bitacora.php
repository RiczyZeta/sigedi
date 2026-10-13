<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Sprint 4 - Bitácora digital (HU13 automática, HU14 manual).
 * Los registros son inmutables: no se pueden editar ni eliminar desde la aplicación.
 */
class Bitacora extends Model
{
    public const TIPO_AUTOMATICA = 'automatica';
    public const TIPO_MANUAL = 'manual';

    public const TIPOS = [
        self::TIPO_AUTOMATICA => 'Automática',
        self::TIPO_MANUAL => 'Manual',
    ];

    /** Acciones registradas: etiqueta, icono (Bootstrap Icons) y color. */
    public const ACCIONES = [
        // Dispositivos (HU13)
        'registro_dispositivo' => ['Registro de dispositivo', 'bi-plus-circle-fill', 'success'],
        'actualizacion_dispositivo' => ['Actualización de datos', 'bi-pencil-square', 'secondary'],
        'cambio_estado_dispositivo' => ['Cambio de estado', 'bi-arrow-repeat', 'warning'],
        'cambio_ubicacion' => ['Cambio de ubicación', 'bi-geo-alt-fill', 'info'],
        'cambio_responsable' => ['Cambio de responsable', 'bi-person-badge-fill', 'info'],
        // Incidencias (HU13)
        'registro_incidencia' => ['Registro de incidencia', 'bi-exclamation-triangle-fill', 'danger'],
        'asignacion' => ['Asignación de técnico', 'bi-person-check-fill', 'primary'],
        'cambio_estado_incidencia' => ['Cambio de estado', 'bi-arrow-repeat', 'primary'],
        'resolucion' => ['Resolución', 'bi-check-circle-fill', 'success'],
        'cierre' => ['Cierre', 'bi-lock-fill', 'dark'],
        'reapertura' => ['Reapertura', 'bi-arrow-counterclockwise', 'warning'],
        'reclasificacion' => ['Reclasificación de prioridad', 'bi-sliders', 'secondary'],
        // Mantenimientos manuales (HU14)
        'mantenimiento_preventivo' => ['Mantenimiento preventivo', 'bi-shield-check', 'success'],
        'mantenimiento_correctivo' => ['Mantenimiento correctivo', 'bi-tools', 'danger'],
    ];

    /** HU14: tipos de mantenimiento => acción que se guarda. */
    public const MANTENIMIENTOS = [
        'preventivo' => 'mantenimiento_preventivo',
        'correctivo' => 'mantenimiento_correctivo',
    ];

    protected $table = 'bitacoras';

    protected $fillable = [
        'user_id',
        'dispositivo_id',
        'incidencia_id',
        'tipo',
        'accion',
        'descripcion',
        'fecha',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'datetime'];
    }

    protected static function booted(): void
    {
        // La fecha de la actividad es, por defecto, el momento del registro.
        static::creating(function (Bitacora $registro) {
            $registro->tipo ??= self::TIPO_AUTOMATICA;
            $registro->fecha ??= now();
        });

        // HU13: «Los registros no se pueden editar ni eliminar».
        static::updating(function () {
            throw new LogicException('Los registros de la bitácora no se pueden modificar.');
        });

        static::deleting(function () {
            throw new LogicException('Los registros de la bitácora no se pueden eliminar.');
        });
    }

    /** Registra una acción automática con el usuario autenticado (HU13). */
    public static function registrar(
        string $accion,
        string $descripcion,
        ?Dispositivo $dispositivo = null,
        ?Incidencia $incidencia = null,
    ): self {
        return static::create([
            'user_id' => auth()->id(),
            'dispositivo_id' => $dispositivo?->id ?? $incidencia?->dispositivo_id,
            'incidencia_id' => $incidencia?->id,
            'tipo' => self::TIPO_AUTOMATICA,
            'accion' => $accion,
            'descripcion' => $descripcion,
        ]);
    }

    /** Filtros combinables del módulo de bitácora. */
    public function scopeFiltrar(Builder $consulta, array $filtros): Builder
    {
        $texto = trim((string) ($filtros['q'] ?? ''));

        return $consulta
            ->when($texto !== '', function (Builder $q) use ($texto) {
                $patron = '%'.addcslashes($texto, '\\%_').'%';
                $q->where(function (Builder $q) use ($patron) {
                    $q->where('descripcion', 'like', $patron)
                        ->orWhereHas('dispositivo', fn ($d) => $d->where('codigo_patrimonial', 'like', $patron))
                        ->orWhereHas('incidencia', fn ($i) => $i->where('codigo', 'like', $patron));
                });
            })
            ->when(array_key_exists($filtros['tipo'] ?? '', self::TIPOS), fn ($q) => $q->where('tipo', $filtros['tipo']))
            ->when(array_key_exists($filtros['accion'] ?? '', self::ACCIONES), fn ($q) => $q->where('accion', $filtros['accion']))
            ->when(! empty($filtros['desde']), fn ($q) => $q->whereDate('fecha', '>=', $filtros['desde']))
            ->when(! empty($filtros['hasta']), fn ($q) => $q->whereDate('fecha', '<=', $filtros['hasta']));
    }

    /** Orden cronológico: de la más reciente a la más antigua (HU15). */
    public function scopeRecientes(Builder $consulta): Builder
    {
        return $consulta->orderByDesc('fecha')->orderByDesc('id');
    }

    public function etiquetaAccion(): string
    {
        return self::ACCIONES[$this->accion][0] ?? $this->accion;
    }

    public function iconoAccion(): string
    {
        return self::ACCIONES[$this->accion][1] ?? 'bi-journal-text';
    }

    public function colorAccion(): string
    {
        return self::ACCIONES[$this->accion][2] ?? 'secondary';
    }

    public function esManual(): bool
    {
        return $this->tipo === self::TIPO_MANUAL;
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(Dispositivo::class, 'dispositivo_id');
    }

    public function incidencia(): BelongsTo
    {
        return $this->belongsTo(Incidencia::class, 'incidencia_id');
    }
}
