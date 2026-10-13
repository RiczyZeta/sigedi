<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sprint 5 - Mensaje recibido o enviado por el asistente de WhatsApp. */
class WhatsappMensaje extends Model
{
    public const ENTRANTE = 'entrante';
    public const SALIENTE = 'saliente';

    protected $table = 'whatsapp_mensajes';

    protected $fillable = [
        'telefono',
        'user_id',
        'direccion',
        'wa_message_id',
        'tipo',
        'contenido',
        'intencion',
        'interprete',
        'estado_envio',
        'error',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
