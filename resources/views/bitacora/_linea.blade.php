{{--
    Línea de tiempo de la bitácora (HU13, HU14, HU15).
    Variables: $registros (colección o paginador de Bitacora), $mostrarDispositivo (bool).
--}}
@if($registros->isEmpty())
    <p class="text-muted text-center mb-0 py-2">No hay actividades registradas.</p>
@else
    <ul class="list-unstyled sg-timeline mb-0">
        @foreach($registros as $r)
            <li class="sg-timeline-item">
                <span class="sg-timeline-dot bg-{{ $r->colorAccion() }}-subtle text-{{ $r->colorAccion() }}-emphasis">
                    <i class="bi {{ $r->iconoAccion() }}"></i>
                </span>
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="fw-semibold">{{ $r->etiquetaAccion() }}</span>
                        <span class="badge {{ $r->esManual() ? 'bg-warning text-dark' : 'bg-light text-secondary border' }}">
                            {{ \App\Models\Bitacora::TIPOS[$r->tipo] ?? $r->tipo }}
                        </span>
                        @if(($mostrarDispositivo ?? true) && $r->dispositivo)
                            <a href="{{ route('dispositivos.show', $r->dispositivo) }}" class="small text-decoration-none">
                                <i class="bi bi-pc-display"></i> {{ $r->dispositivo->codigo_patrimonial }}</a>
                        @endif
                        @if($r->incidencia)
                            <a href="{{ route('incidencias.show', $r->incidencia) }}" class="small text-decoration-none">
                                <i class="bi bi-ticket-detailed"></i> {{ $r->incidencia->codigo }}</a>
                        @endif
                    </div>
                    <div class="small">{!! nl2br(e($r->descripcion)) !!}</div>
                    <div class="small text-muted">
                        <i class="bi bi-clock"></i> {{ $r->fecha?->format('d/m/Y H:i') }}
                        · <i class="bi bi-person"></i> {{ $r->usuario?->name ?? 'Sistema' }}
                        @if($r->esManual() && $r->created_at && ! $r->fecha?->isSameDay($r->created_at))
                            · registrado el {{ $r->created_at->format('d/m/Y') }}
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
