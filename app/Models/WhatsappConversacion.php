<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sprint 5 - HU20: estado de la conversación de reporte por WhatsApp. */
class WhatsappConversacion extends Model
{
    public const INICIO = 'inicio';
    public const ESPERAR_DISPOSITIVO = 'esperar_dispositivo';
    public const ESPERAR_DESCRIPCION = 'esperar_descripcion';
    public const CONFIRMAR_REPORTE = 'confirmar_reporte';

    /** Minutos de inactividad tras los cuales la conversación se reinicia. */
    public const MINUTOS_VIGENCIA = 30;

    protected $table = 'whatsapp_conversaciones';

    protected $fillable = ['telefono', 'user_id', 'paso', 'datos', 'expira_at'];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'expira_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vencida(): bool
    {
        return $this->expira_at !== null && $this->expira_at->isPast();
    }

    public function avanzar(string $paso, array $datos = []): void
    {
        $this->update([
            'paso' => $paso,
            'datos' => $datos,
            'expira_at' => now()->addMinutes(self::MINUTOS_VIGENCIA),
        ]);
    }

    public function reiniciar(): void
    {
        $this->update(['paso' => self::INICIO, 'datos' => null, 'expira_at' => null]);
    }

    public function dato(string $clave, mixed $defecto = null): mixed
    {
        return $this->datos[$clave] ?? $defecto;
    }
}
