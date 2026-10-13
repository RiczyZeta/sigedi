@extends('layouts.app')

@section('titulo', 'Asistente WhatsApp')

@section('contenido')
    {{-- Sprint 5 - HU17: estado de la conexión e indicadores del asistente --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-whatsapp"></i> Asistente virtual de WhatsApp</h1>
            <p class="text-muted small mb-0">WhatsApp Business Cloud API para los mensajes y Gemini API para interpretar el lenguaje natural.</p>
        </div>
        <span class="badge fs-6 {{ $listo ? 'bg-success' : 'bg-warning text-dark' }}">
            {{ $listo ? 'Conexión configurada' : 'Configuración incompleta' }}
        </span>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted fw-bold mb-3">Configuración (.env)</h2>
                    <ul class="list-unstyled mb-3">
                        @foreach($conexion as [$etiqueta, $ok])
                            <li class="mb-1">
                                <i class="bi {{ $ok ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }}"></i>
                                {{ $etiqueta }}
                            </li>
                        @endforeach
                    </ul>
                    <label class="form-label small mb-1" for="url-webhook">URL del webhook para Meta (debe ser HTTPS público)</label>
                    <input id="url-webhook" type="text" class="form-control form-control-sm" value="{{ $urlWebhook }}" readonly>
                    <div class="form-text">Sin API key de Gemini, el asistente interpreta los mensajes con reglas de palabras clave.</div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="row g-2">
                @foreach($tarjetas as [$etiqueta, $valor, $icono, $color])
                    <div class="col-6 col-md-4">
                        <div class="card h-100">
                            <div class="sg-stat">
                                <div class="sg-stat-icon bg-{{ $color }}-subtle text-{{ $color }}-emphasis"><i class="bi {{ $icono }}"></i></div>
                                <div>
                                    <div class="sg-stat-num">{{ $valor }}</div>
                                    <div class="sg-stat-lbl">{{ $etiqueta }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Últimos mensajes</h2></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Sentido</th>
                    <th>Número</th>
                    <th>Usuario</th>
                    <th>Mensaje</th>
                    <th>Intención / envío</th>
                </tr>
                </thead>
                <tbody>
                @forelse($mensajes as $m)
                    <tr>
                        <td class="small text-nowrap">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($m->direccion === \App\Models\WhatsappMensaje::ENTRANTE)
                                <span class="badge bg-light text-secondary border"><i class="bi bi-arrow-down-left"></i> Recibido</span>
                            @else
                                <span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-arrow-up-right"></i> Enviado</span>
                            @endif
                        </td>
                        <td class="small">{{ \App\Support\Telefono::enmascarar($m->telefono) }}</td>
                        <td class="small">{{ $m->usuario?->name ?? 'No registrado' }}</td>
                        <td class="small" style="max-width: 28rem">{!! nl2br(e(\Illuminate\Support\Str::limit($m->contenido, 220))) !!}</td>
                        <td class="small">
                            @if($m->intencion)
                                <span class="badge bg-info-subtle text-info-emphasis">{{ str_replace('_', ' ', $m->intencion) }}</span>
                                <span class="text-muted">({{ $m->interprete }})</span>
                            @elseif($m->estado_envio)
                                <span class="badge {{ $m->estado_envio === 'error' ? 'bg-danger' : 'bg-secondary' }}">{{ $m->estado_envio }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aún no hay mensajes. Escribe al número de WhatsApp de prueba para iniciar.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $mensajes->links('pagination::bootstrap-5') }}</div>
@endsection
