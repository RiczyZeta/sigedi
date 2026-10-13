<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const ADMINISTRADOR = 'administrador';
    public const TECNICO = 'tecnico';
    public const USUARIO = 'usuario';

    protected $table = 'roles';

    protected $fillable = ['nombre', 'descripcion'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
