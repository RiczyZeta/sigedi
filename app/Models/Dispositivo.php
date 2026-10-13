<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispositivo extends Model
{
    /** HU05: estados permitidos. */
    public const ESTADOS = ['operativo', 'en_mantenimiento', 'inoperativo', 'de_baja'];

    public const ETIQUETAS = [
        'operativo' => 'Operativo',
        'en_mantenimiento' => 'En mantenimiento',
        'inoperativo' => 'Inoperativo',
        'de_baja' => 'De baja',
    ];

    public const COLORES = [
        'operativo' => 'success',
        'en_mantenimiento' => 'warning',
        'inoperativo' => 'danger',
        'de_baja' => 'secondary',
    ];

    protected $table = 'dispositivos';

    protected $fillable = [
        'codigo_patrimonial',
        'tipo',
        'marca',
        'modelo',
        'serie',
        'caracteristicas',
        'estado',
        'ubicacion_id',
        'responsable_id',
    ];

    /**
     * HU07: búsqueda con filtros combinables.
     * «q» acepta coincidencias parciales del código patrimonial (también de serie, marca y modelo).
     */
    public function scopeFiltrar(Builder $consulta, array $filtros): Builder
    {
        $texto = trim((string) ($filtros['q'] ?? ''));

        return $consulta
            ->when($texto !== '', function (Builder $q) use ($texto) {
                $patron = '%'.addcslashes($texto, '\\%_').'%';
                $q->where(function (Builder $q) use ($patron) {
                    $q->where('codigo_patrimonial', 'like', $patron)
                        ->orWhere('serie', 'like', $patron)
                        ->orWhere('marca', 'like', $patron)
                        ->orWhere('modelo', 'like', $patron);
                });
            })
            ->when(filled($filtros['tipo'] ?? null), fn ($q) => $q->where('tipo', $filtros['tipo']))
            ->when(filled($filtros['ubicacion_id'] ?? null), fn ($q) => $q->where('ubicacion_id', (int) $filtros['ubicacion_id']))
            ->when(
                in_array($filtros['estado'] ?? null, self::ESTADOS, true),
                fn ($q) => $q->where('estado', $filtros['estado'])
            );
    }

    public function etiquetaEstado(): string
    {
        return self::ETIQUETAS[$this->estado] ?? $this->estado;
    }

    public function colorEstado(): string
    {
        return self::COLORES[$this->estado] ?? 'secondary';
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencia::class, 'dispositivo_id');
    }

    public function bitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class, 'dispositivo_id');
    }
}
