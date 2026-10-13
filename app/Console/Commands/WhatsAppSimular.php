<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\AsistenteWhatsApp;
use Illuminate\Console\Command;

/**
 * Sprint 5 - Prueba el asistente sin WhatsApp ni Internet público: procesa el texto como si llegara
 * del número indicado y muestra la respuesta en la terminal (no se envía nada a Meta).
 *
 *   php artisan whatsapp:simular 51987654321 "estado de PC-LAB1-05"
 */
class WhatsAppSimular extends Command
{
    protected $signature = 'whatsapp:simular {numero : Número registrado en SIGEDI} {texto* : Mensaje del usuario}';

    protected $description = 'Simula un mensaje entrante de WhatsApp y muestra la respuesta del asistente';

    public function handle(AsistenteWhatsApp $asistente): int
    {
        $texto = implode(' ', $this->argument('texto'));
        $respuesta = $asistente->procesar($this->argument('numero'), $texto, enviar: false);

        $this->line('<fg=gray>Usuario:</> '.$texto);
        $this->newLine();
        $this->line('<fg=green>SIGEDI:</>');
        $this->line((string) $respuesta);

        return self::SUCCESS;
    }
}
