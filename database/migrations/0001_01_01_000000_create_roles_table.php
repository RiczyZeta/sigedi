<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * HT02 - Tabla de roles. Se llama igual que la migración de usuarios
 * (mismo prefijo 0001_01_01_000000) y, por orden alfabético, se ejecuta
 * ANTES que create_users_table, porque users tiene una clave foránea a roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30)->unique();   // administrador, tecnico, usuario
            $table->string('descripcion', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
