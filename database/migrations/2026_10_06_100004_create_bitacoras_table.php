<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacoras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dispositivo_id')->nullable()->constrained('dispositivos')->nullOnDelete();
            $table->foreignId('incidencia_id')->nullable()->constrained('incidencias')->nullOnDelete();
            $table->string('tipo', 15)->default('automatica');       // automatica | manual (HU13, HU14)
            $table->string('accion', 60);
            $table->text('descripcion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacoras');
    }
};
