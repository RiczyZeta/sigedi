<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositivos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_patrimonial', 40)->unique();      // HU03: sin duplicados
            $table->string('tipo', 40);                              // laptop, PC, impresora, proyector...
            $table->string('marca', 60)->nullable();
            $table->string('modelo', 80)->nullable();
            $table->string('serie', 80)->nullable();
            $table->text('caracteristicas')->nullable();
            // HU05: operativo, en_mantenimiento, inoperativo, de_baja
            $table->string('estado', 30)->default('operativo')->index();
            $table->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos');
    }
};
