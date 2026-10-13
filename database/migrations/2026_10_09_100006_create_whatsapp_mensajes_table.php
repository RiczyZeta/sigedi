<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Sprint 5 - HU17, HU18, HU20: registro de los mensajes recibidos y enviados por WhatsApp.
 * Permite evitar procesar dos veces el mismo mensaje (Meta reintenta los webhooks)
 * y medir el indicador del OE5 (consultas respondidas por el asistente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_mensajes', function (Blueprint $table) {
            $table->id();
            $table->string('telefono', 20)->index();                     // número normalizado: 51987654321
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('direccion', 10);                             // entrante | saliente
            $table->string('wa_message_id', 120)->nullable()->unique();  // id de Meta (idempotencia)
            $table->string('tipo', 20)->default('text');                 // text, image, audio...
            $table->text('contenido');
            $table->string('intencion', 40)->nullable()->index();        // intención detectada (entrantes)
            $table->string('interprete', 10)->nullable();                // gemini | reglas
            $table->string('estado_envio', 15)->nullable();              // enviado | simulado | error (salientes)
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_mensajes');
    }
};
