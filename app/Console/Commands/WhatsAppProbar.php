<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\WhatsAppCliente;
use Illuminate\Console\Command;

/** Sprint 5 - HU17: envía un mensaje de prueba para comprobar la conexión con WhatsApp Cloud API. */
class WhatsAppProbar extends Command
{
    protected $signature = 'whatsapp:probar {numero : Número de destino, por ejemplo 51987654321}
                            {--mensaje=Mensaje de prueba de SIGEDI : Texto a enviar}';

    protected $description = 'Envía un mensaje de prueba por WhatsApp Cloud API';

    public function handle(WhatsAppCliente $cliente): int
    {
        if (! $cliente->configurado()) {
            $this->error('Faltan WHATSAPP_TOKEN o WHATSAPP_PHONE_NUMBER_ID en el archivo .env.');

            return self::FAILURE;
        }

        $registro = $cliente->enviarTexto($this->argument('numero'), $this->option('mensaje'));

        if ($registro->estado_envio !== 'enviado') {
            $this->error('Meta rechazó el mensaje: '.$registro->error);
            $this->line('Recuerda: si el usuario no te escribió en las últimas 24 horas, Meta solo permite plantillas.');

            return self::FAILURE;
        }

        $this->info("Mensaje enviado (id {$registro->wa_message_id}).");

        return self::SUCCESS;
    }
}
