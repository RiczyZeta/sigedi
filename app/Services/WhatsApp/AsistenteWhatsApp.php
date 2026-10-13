<?php

namespace App\Services\WhatsApp;

use App\Models\Dispositivo;
use App\Models\Incidencia;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappConversacion;
use App\Models\WhatsappMensaje;
use App\Support\Telefono;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Sprint 5 - Asistente virtual de SIGEDI en WhatsApp.
 * HU18: consultas en lenguaje natural sobre dispositivos e incidencias.
 * HU20: reporte de incidencias con confirmación del dispositivo y entrega del número de ticket.
 *
 * Todas las respuestas se arman con datos de la base de datos (RNF08): si un dato no existe,
 * el asistente lo dice en lugar de inventarlo.
 */
class AsistenteWhatsApp
{
    private const MIN_DESCRIPCION = 10;

    public function __construct(
        private readonly Interprete $interprete,
        private readonly WhatsAppCliente $cliente,
    ) {
    }

    /**
     * Procesa un mensaje entrante y responde en el mismo chat.
     *
     * @return string|null  texto de la respuesta (null si el mensaje ya se había procesado)
     */
    public function procesar(string $telefono, string $texto, ?string $waMessageId = null, string $tipo = 'text', bool $enviar = true): ?string
    {
        $telefono = Telefono::normalizar($telefono);

        // Meta puede reenviar el mismo webhook: cada mensaje se procesa una sola vez.
        if ($waMessageId && WhatsappMensaje::where('wa_message_id', $waMessageId)->exists()) {
            return null;
        }

        $usuario = $this->identificar($telefono);

        $entrante = WhatsappMensaje::create([
            'telefono' => $telefono,
            'user_id' => $usuario?->id,
            'direccion' => WhatsappMensaje::ENTRANTE,
            'wa_message_id' => $waMessageId,
            'tipo' => $tipo,
            'contenido' => $tipo === 'text' ? Str::limit($texto, 2000) : "[{$tipo}]",
        ]);

        $respuesta = match (true) {
            $usuario === null => $this->respuestaNoRegistrado(),
            $tipo !== 'text' => 'Por ahora solo puedo leer mensajes de texto. Escribe tu consulta o escribe *ayuda* para ver ejemplos.',
            default => $this->atender($usuario, $telefono, $texto, $entrante),
        };

        $this->cliente->simular(! $enviar)->enviarTexto($telefono, $respuesta, $usuario?->id);

        return $respuesta;
    }

    /** Identifica al usuario por su número de WhatsApp registrado en SIGEDI (requisito de HU18 y HU20). */
    public function identificar(string $telefono): ?User
    {
        return User::with('role')
            ->where('activo', true)
            ->whereNotNull('whatsapp')
            ->get()
            ->first(fn (User $u) => Telefono::iguales($u->whatsapp, $telefono));
    }

    private function atender(User $usuario, string $telefono, string $texto, WhatsappMensaje $entrante): string
    {
        // Se indica el paso inicial explícitamente: al crear el registro, Eloquent no carga los valores
        // por defecto de la base de datos y «paso» quedaría en null.
        $conversacion = WhatsappConversacion::firstOrCreate(
            ['telefono' => $telefono],
            ['user_id' => $usuario->id, 'paso' => WhatsappConversacion::INICIO],
        );
        $conversacion->paso ??= WhatsappConversacion::INICIO;

        if ($conversacion->paso !== WhatsappConversacion::INICIO && $conversacion->vencida()) {
            $conversacion->reiniciar();
        }

        $datos = $this->interprete->interpretar($texto, $conversacion->paso);
        $entrante->update(['intencion' => $datos['intencion'], 'interprete' => $datos['interprete']]);

        // Las acciones del asistente quedan en la bitácora con el usuario que escribió (HU13).
        Auth::setUser($usuario);

        try {
            return match ($conversacion->paso) {
                WhatsappConversacion::CONFIRMAR_REPORTE => $this->pasoConfirmar($usuario, $conversacion, $datos),
                WhatsappConversacion::ESPERAR_DISPOSITIVO => $this->pasoDispositivo($conversacion, $datos, $texto),
                WhatsappConversacion::ESPERAR_DESCRIPCION => $this->pasoDescripcion($conversacion, $datos, $texto),
                default => $this->segunIntencion($usuario, $conversacion, $datos),
            };
        } finally {
            Auth::forgetUser();
        }
    }

    // ------------------------------------------------------------------ intenciones

    private function segunIntencion(User $usuario, WhatsappConversacion $conversacion, array $datos): string
    {
        return match ($datos['intencion']) {
            'consultar_dispositivo' => $this->consultarDispositivo($datos['codigo_dispositivo'], $datos['codigo_mencionado']),
            'consultar_incidencia' => $this->consultarIncidencia($usuario, $datos['codigo_incidencia']),
            'mis_incidencias' => $this->misIncidencias($usuario),
            'reportar_incidencia' => $this->iniciarReporte($conversacion, $datos),
            'saludo', 'ayuda' => $this->ayuda($usuario),
            default => "No entendí tu mensaje.\n\n".$this->ejemplos(),
        };
    }

    /** HU18: estado real de un dispositivo registrado. */
    private function consultarDispositivo(?string $codigo, ?string $mencionado): string
    {
        if ($codigo === null) {
            return $mencionado
                ? "No encontré un dispositivo con el código *{$mencionado}*. Revisa el código patrimonial en la etiqueta del equipo."
                : 'Indica el código patrimonial del equipo. Ejemplo: *estado de PC-LAB1-05*';
        }

        $d = Dispositivo::with('ubicacion')->where('codigo_patrimonial', $codigo)->first();

        if ($d === null) {
            return "No encontré un dispositivo con el código *{$codigo}*.";
        }
        $abiertas = $d->incidencias()->where('estado', '!=', 'cerrada')->count();
        $modelo = trim(($d->marca ?? '').' '.($d->modelo ?? ''));

        return implode("\n", array_filter([
            "*{$d->codigo_patrimonial}* — {$d->tipo}".($modelo !== '' ? " {$modelo}" : ''),
            'Ubicación: '.($d->ubicacion?->nombre ?? 'sin ubicación registrada'),
            "Estado: *{$d->etiquetaEstado()}*",
            'Incidencias abiertas: '.$abiertas,
        ]));
    }

    /** HU18: estado de una incidencia. El usuario solicitante solo consulta las suyas (HU16). */
    private function consultarIncidencia(User $usuario, ?string $codigo): string
    {
        if ($codigo === null) {
            return 'Indica el código de la incidencia. Ejemplo: *INC-'.now()->year.'-001*';
        }

        $i = Incidencia::with(['dispositivo', 'tecnico'])->where('codigo', $codigo)->first();

        if ($i === null) {
            return "No encontré la incidencia *{$codigo}*. Verifica el código que recibiste al reportarla.";
        }

        if ($usuario->tieneRol(Role::USUARIO) && (int) $i->solicitante_id !== (int) $usuario->id) {
            return 'Solo puedes consultar las incidencias que registraste.';
        }

        return implode("\n", array_filter([
            "*{$i->codigo}* — {$i->dispositivo->codigo_patrimonial} ({$i->dispositivo->tipo})",
            "Estado: *{$i->etiquetaEstado()}* · Prioridad: {$i->etiquetaPrioridad()}",
            'Técnico: '.($i->tecnico?->name ?? 'aún sin asignar'),
            'Registrada: '.$i->created_at->format('d/m/Y H:i'),
            $i->solucion && in_array($i->estado, ['resuelta', 'cerrada'], true) ? 'Solución: '.Str::limit($i->solucion, 300) : null,
        ]));
    }

    /** HU18: últimas incidencias del usuario (las que reportó o, si es técnico, las asignadas). */
    private function misIncidencias(User $usuario): string
    {
        $esTecnico = $usuario->tieneRol(Role::TECNICO);

        $incidencias = Incidencia::with('dispositivo')
            ->where($esTecnico ? 'tecnico_id' : 'solicitante_id', $usuario->id)
            ->where('estado', '!=', 'cerrada')
            ->ordenPrioridad()
            ->limit(5)
            ->get();

        if ($incidencias->isEmpty()) {
            return $esTecnico ? 'No tienes incidencias abiertas asignadas.' : 'No tienes incidencias abiertas.';
        }

        $lineas = $incidencias->map(fn (Incidencia $i) => "• *{$i->codigo}* — {$i->dispositivo->codigo_patrimonial}: {$i->etiquetaEstado()} ({$i->etiquetaPrioridad()})");

        return ($esTecnico ? "Incidencias abiertas asignadas a ti:\n" : "Tus incidencias abiertas:\n")
            .$lineas->implode("\n")
            ."\n\nEscribe el código para ver el detalle.";
    }

    // ------------------------------------------------------------------ HU20: reporte

    private function iniciarReporte(WhatsappConversacion $conversacion, array $datos): string
    {
        $descripcion = $datos['descripcion'];
        $categoria = $datos['categoria'] ?? 'Otro';

        if ($datos['codigo_dispositivo'] === null) {
            $conversacion->avanzar(WhatsappConversacion::ESPERAR_DISPOSITIVO, compact('descripcion', 'categoria'));

            $aviso = $datos['codigo_mencionado'] ? "No encontré el código *{$datos['codigo_mencionado']}*. " : '';

            return $aviso.'¿Cuál es el código patrimonial del equipo con la falla? Lo encuentras en la etiqueta del equipo. (Escribe *cancelar* para salir)';
        }

        return $this->conDispositivo($conversacion, $datos['codigo_dispositivo'], $descripcion, $categoria);
    }

    private function pasoDispositivo(WhatsappConversacion $conversacion, array $datos, string $texto): string
    {
        if ($datos['intencion'] === 'cancelar') {
            $conversacion->reiniciar();

            return 'Reporte cancelado.';
        }

        if ($datos['codigo_dispositivo'] === null) {
            $mencionado = $datos['codigo_mencionado'];

            return ($mencionado ? "No encontré el código *{$mencionado}*. " : '')
                .'Escribe el código patrimonial del equipo (por ejemplo *PC-LAB1-05*) o *cancelar* para salir.';
        }

        return $this->conDispositivo(
            $conversacion,
            $datos['codigo_dispositivo'],
            $conversacion->dato('descripcion'),
            $conversacion->dato('categoria', 'Otro'),
        );
    }

    private function pasoDescripcion(WhatsappConversacion $conversacion, array $datos, string $texto): string
    {
        if ($datos['intencion'] === 'cancelar') {
            $conversacion->reiniciar();

            return 'Reporte cancelado.';
        }

        $categoria = $conversacion->dato('categoria');
        if ($categoria === null || $categoria === 'Otro') {
            $categoria = $datos['categoria'] ?? $this->interprete->porReglas('falla '.$texto)['categoria'];
        }

        return $this->conDispositivo($conversacion, $conversacion->dato('codigo'), trim($texto), $categoria);
    }

    /** Valida el dispositivo y la descripción, y pide confirmación antes de registrar (criterio de HU20). */
    private function conDispositivo(WhatsappConversacion $conversacion, string $codigo, ?string $descripcion, string $categoria): string
    {
        $dispositivo = Dispositivo::with('ubicacion')->where('codigo_patrimonial', $codigo)->first();

        if ($dispositivo === null) {
            $conversacion->avanzar(WhatsappConversacion::ESPERAR_DISPOSITIVO, compact('descripcion', 'categoria'));

            return "No encontré el código *{$codigo}*. Escribe el código patrimonial del equipo o *cancelar* para salir.";
        }

        if ($dispositivo->estado === 'de_baja') {
            $conversacion->reiniciar();

            return "El equipo *{$dispositivo->codigo_patrimonial}* está dado de baja y no admite reportes. Comunícate con el área de Cómputo.";
        }

        // La descripción debe explicar la falla: no cuenta el código del equipo (p. ej. «falla IMP-DIR-01»).
        $sinCodigo = trim(str_ireplace($dispositivo->codigo_patrimonial, '', (string) $descripcion));

        if (mb_strlen($sinCodigo) < self::MIN_DESCRIPCION) {
            $conversacion->avanzar(WhatsappConversacion::ESPERAR_DESCRIPCION, ['codigo' => $codigo, 'categoria' => $categoria]);

            return "Describe brevemente la falla de *{$dispositivo->codigo_patrimonial}* (qué ocurre y desde cuándo).";
        }

        $conversacion->avanzar(WhatsappConversacion::CONFIRMAR_REPORTE, [
            'codigo' => $dispositivo->codigo_patrimonial,
            'descripcion' => Str::limit(trim($descripcion), 1000),
            'categoria' => $categoria,
        ]);

        return implode("\n", [
            'Vas a reportar esta falla:',
            "Equipo: *{$dispositivo->codigo_patrimonial}* — {$dispositivo->tipo}".($dispositivo->ubicacion ? " ({$dispositivo->ubicacion->nombre})" : ''),
            "Categoría: {$categoria}",
            'Falla: «'.Str::limit(trim($descripcion), 300).'»',
            '',
            'Responde *SÍ* para registrarla o *NO* para cancelar.',
        ]);
    }

    private function pasoConfirmar(User $usuario, WhatsappConversacion $conversacion, array $datos): string
    {
        if ($datos['intencion'] === 'cancelar') {
            $conversacion->reiniciar();

            return 'Reporte cancelado. No se registró ninguna incidencia.';
        }

        if ($datos['intencion'] !== 'confirmar') {
            return 'Responde *SÍ* para registrar la incidencia o *NO* para cancelar.';
        }

        $dispositivo = Dispositivo::where('codigo_patrimonial', $conversacion->dato('codigo'))->first();

        if ($dispositivo === null || $dispositivo->estado === 'de_baja') {
            $conversacion->reiniciar();

            return 'El equipo ya no está disponible para reportes. Inicia un nuevo reporte, por favor.';
        }

        // Impacto y urgencia medios por defecto: el técnico puede reclasificar la incidencia (HU10).
        $incidencia = Incidencia::create([
            'codigo' => Incidencia::generarCodigo(),
            'dispositivo_id' => $dispositivo->id,
            'solicitante_id' => $usuario->id,
            'categoria' => in_array($conversacion->dato('categoria'), Incidencia::CATEGORIAS, true) ? $conversacion->dato('categoria') : 'Otro',
            'descripcion' => $conversacion->dato('descripcion'),
            'impacto' => 'medio',
            'urgencia' => 'media',
            'prioridad' => Incidencia::calcularPrioridad('medio', 'media'),
            'estado' => 'registrada',
            'canal' => 'whatsapp',
        ]);

        $conversacion->reiniciar();

        return "Incidencia registrada correctamente.\nTu número de ticket es *{$incidencia->codigo}*.\n"
            ."Guárdalo para consultar su avance: escribe *{$incidencia->codigo}* cuando quieras.";
    }

    // ------------------------------------------------------------------ textos

    private function ayuda(User $usuario): string
    {
        $nombre = Str::of($usuario->name)->before(' ')->toString();

        return "Hola, {$nombre}. Soy el asistente de SIGEDI de la I.E. INA 54.\n\n".$this->ejemplos();
    }

    private function ejemplos(): string
    {
        return implode("\n", [
            'Puedes escribirme, por ejemplo:',
            '• *estado de PC-LAB1-05* — estado de un equipo',
            '• *INC-'.now()->year.'-001* — avance de una incidencia',
            '• *mis incidencias* — tus incidencias abiertas',
            '• *la impresora IMP-DIR-01 no imprime* — reportar una falla',
        ]);
    }

    private function respuestaNoRegistrado(): string
    {
        return 'Este número no está registrado en SIGEDI. Solicita al área de Cómputo de la I.E. INA 54 que registre tu número de WhatsApp.';
    }
}
