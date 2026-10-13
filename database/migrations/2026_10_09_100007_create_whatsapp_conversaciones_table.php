<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Sprint 5 - HU20: estado de la conversación de cada número.
 * Guarda el paso del reporte por WhatsApp (pedir dispositivo, pedir descripción, confirmar)
 * y los datos recogidos hasta registrar la incidencia. La conversación vence a los 30 minutos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversaciones', function (Blueprint $table) {
            $table->id();
            $table->string('telefono', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('paso', 30)->default('inicio');
            $table->json('datos')->nullable();
            $table->timestamp('expira_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversaciones');
    }
};
