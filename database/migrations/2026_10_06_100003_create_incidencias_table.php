<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();                  // INC-AAAA-NNN (HU09)
            $table->foreignId('dispositivo_id')->constrained('dispositivos');
            $table->foreignId('solicitante_id')->constrained('users');
            $table->foreignId('tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('categoria', 40);
            $table->text('descripcion');
            $table->string('impacto', 10);                           // alto, medio, bajo
            $table->string('urgencia', 10);                          // alta, media, baja
            $table->string('prioridad', 10);                         // calculada: impacto x urgencia (HU10)
            $table->string('estado', 20)->default('registrada')->index();   // flujo ITIL 4 (HU12)
            $table->string('canal', 15)->default('web');             // web | whatsapp
            $table->text('solucion')->nullable();
            $table->timestamp('fecha_asignacion')->nullable();
            $table->timestamp('fecha_cierre')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};
