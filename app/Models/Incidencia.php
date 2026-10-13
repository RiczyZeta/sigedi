<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incidencia extends Model
{
    /** Flujo de estados (ITIL 4). */
    public const ESTADOS = ['registrada', 'asignada', 'en_atencion', 'resuelta', 'cerrada'];

    public const ETIQUETAS_ESTADO = [
        'registrada' => 'Registrada',
        'asignada' => 'Asignada',
        'en_atencion' => 'En atención',
        'resuelta' => 'Resuelta',
        'cerrada' => 'Cerrada',
    ];

    public const CLASES_ESTADO = [
        'registrada' => 'bg-secondary',
        'asignada' => 'bg-info text-dark',
        'en_atencion' => 'bg-primary',
        'resuelta' => 'bg-success',
        'cerrada' => 'bg-dark',
    ];

    /** Transiciones válidas: estado actual => estados a los que puede pasar (HU12). */
    public const TRANSICIONES = [
        'registrada' => ['asignada'],
        'asignada' => ['en_atencion'],
        'en_atencion' => ['resuelta'],
        'resuelta' => ['cerrada', 'en_atencion'],   // se cierra o se reabre si la solución no funcionó
        'cerrada' => [],
    ];

    public const IMPACTOS = ['alto' => 'Alto', 'medio' => 'Medio', 'bajo' => 'Bajo'];

    public const URGENCIAS = ['alta' => 'Alta', 'media' => 'Media', 'baja' => 'Baja'];

    public const PRIORIDADES = ['critica' => 'Crítica', 'alta' => 'Alta', 'media' => 'Media', 'baja' => 'Baja'];

    public const CLASES_PRIORIDAD = [
        'critica' => 'bg-danger',
        'alta' => 'bg-warning text-dark',
        'media' => 'bg-info text-dark',
        'baja' => 'bg-success',
    ];

    /** Matriz impacto × urgencia => prioridad (HU10). */
    public const MATRIZ = [
        'alto' => ['alta' => 'critica', 'media' => 'alta', 'baja' => 'media'],
        'medio' => ['alta' => 'alta', 'media' => 'media', 'baja' => 'baja'],
        'bajo' => ['alta' => 'media', 'media' => 'baja', 'baja' => 'baja'],
    ];

    public const CATEGORIAS = ['Hardware', 'Software', 'Red e Internet', 'Impresión', 'Periféricos', 'Otro'];

    protected $table = 'incidencias';

    protected $fillable = [
        'codigo',
        'dispositivo_id',
        'solicitante_id',
        'tecnico_id',
        'categoria',
        'descripcion',
        'impacto',
        'urgencia',
        'prioridad',
        'estado',
        'canal',
        'solucion',
        'fecha_asignacion',
        'fecha_cierre',
    ];

    protected function casts(): array
    {
        return [
            'fecha_asignacion' => 'datetime',
            'fecha_cierre' => 'datetime',
        ];
    }

    /** HU10: prioridad según la matriz impacto × urgencia. */
    public static function calcularPrioridad(string $impacto, string $urgencia): string
    {
        return self::MATRIZ[$impacto][$urgencia];
    }

    /** HU09: código único con el formato INC-AAAA-NNN (la numeración reinicia cada año). */
    public static function generarCodigo(?int $anio = null): string
    {
        $anio ??= now()->year;

        // Se ordena primero por longitud para que INC-2026-1000 sea mayor que INC-2026-999
        $ultimo = static::where('codigo', 'like', "INC-{$anio}-%")
            ->orderByRaw('LENGTH(codigo) DESC')
            ->orderByDesc('codigo')
            ->value('codigo');

        $numero = $ultimo ? ((int) substr($ultimo, strrpos($ultimo, '-') + 1)) + 1 : 1;

        return sprintf('INC-%d-%03d', $anio, $numero);
    }

    /**
     * HU07: búsqueda de incidencias con filtros combinables.
     * «q» acepta coincidencias parciales del código de la incidencia o del código patrimonial del dispositivo.
     */
    public function scopeFiltrar(Builder $consulta, array $filtros): Builder
    {
        $texto = trim((string) ($filtros['q'] ?? ''));

        return $consulta
            ->when($texto !== '', function (Builder $q) use ($texto) {
                $patron = '%'.addcslashes($texto, '\\%_').'%';
                $q->where(function (Builder $q) use ($patron) {
                    $q->where('codigo', 'like', $patron)
                        ->orWhereHas('dispositivo', fn ($d) => $d->where('codigo_patrimonial', 'like', $patron));
                });
            })
            ->when(in_array($filtros['estado'] ?? null, self::ESTADOS, true), fn ($q) => $q->where('estado', $filtros['estado']))
            ->when(array_key_exists($filtros['prioridad'] ?? '', self::PRIORIDADES), fn ($q) => $q->where('prioridad', $filtros['prioridad']))
            ->when(in_array($filtros['categoria'] ?? null, self::CATEGORIAS, true), fn ($q) => $q->where('categoria', $filtros['categoria']));
    }

    /** Orden de atención: primero la prioridad más alta y, dentro de ella, la más reciente. */
    public function scopeOrdenPrioridad(Builder $consulta): Builder
    {
        return $consulta
            ->orderByRaw("CASE prioridad WHEN 'critica' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at');
    }

    public function puedePasarA(string $estado): bool
    {
        return in_array($estado, self::TRANSICIONES[$this->estado] ?? [], true);
    }

    public function etiquetaEstado(): string
    {
        return self::ETIQUETAS_ESTADO[$this->estado] ?? $this->estado;
    }

    public function claseEstado(): string
    {
        return self::CLASES_ESTADO[$this->estado] ?? 'bg-secondary';
    }

    public function etiquetaPrioridad(): string
    {
        return self::PRIORIDADES[$this->prioridad] ?? $this->prioridad;
    }

    public function clasePrioridad(): string
    {
        return self::CLASES_PRIORIDAD[$this->prioridad] ?? 'bg-secondary';
    }

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(Dispositivo::class, 'dispositivo_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function bitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class, 'incidencia_id');
    }
}
