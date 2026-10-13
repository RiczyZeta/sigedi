<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'whatsapp',
        'activo',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',   // bcrypt automático (RNF01)
            'activo' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** ¿El usuario tiene alguno de estos roles? */
    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->role?->nombre, $roles, true);
    }

    public function esAdministrador(): bool
    {
        return $this->tieneRol(Role::ADMINISTRADOR);
    }

    /** Administrador y técnico gestionan el inventario de dispositivos. */
    public function puedeGestionarInventario(): bool
    {
        return $this->tieneRol(Role::ADMINISTRADOR, Role::TECNICO);
    }
}
