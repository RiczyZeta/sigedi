# SIGEDI – Bitácora digital (Sprint 4)

## Qué se registra automáticamente (HU13)

Lo registran los observers `DispositivoObserver` e `IncidenciaObserver`, por lo que se cubre cualquier origen del cambio
(formulario, cambio rápido de estado, importación desde Excel y, en el Sprint 5, WhatsApp).

| Entidad | Evento | Acción guardada |
|---|---|---|
| Dispositivo | Registro (formulario o importación) | `registro_dispositivo` |
| Dispositivo | Cambio de estado | `cambio_estado_dispositivo` |
| Dispositivo | Cambio de ubicación / responsable | `cambio_ubicacion` / `cambio_responsable` |
| Dispositivo | Cambio de código, tipo, marca, modelo, serie o características | `actualizacion_dispositivo` |
| Incidencia | Registro | `registro_incidencia` |
| Incidencia | Asignación o reasignación de técnico | `asignacion` |
| Incidencia | Registrada/Asignada → En atención | `cambio_estado_incidencia` |
| Incidencia | → Resuelta / → Cerrada | `resolucion` / `cierre` (incluye la solución) |
| Incidencia | Resuelta → En atención | `reapertura` |
| Incidencia | Cambio de impacto o urgencia | `reclasificacion` |

Cada registro guarda: usuario (`user_id`), dispositivo, incidencia (si aplica), tipo, acción, descripción y fecha.
Las acciones de una incidencia se vinculan también a su dispositivo, por eso aparecen en la ficha del equipo (HU15).

## Registro manual (HU14)

Desde la ficha del dispositivo: tipo **preventivo** o **correctivo**, fecha (no futura) y descripción (10 a 2000 caracteres).
Se guarda con `tipo = manual`. No se admite para equipos dados de baja.

## Inmutabilidad

- No existen rutas para editar ni eliminar registros (solo `GET /bitacora`).
- El modelo `Bitacora` lanza una excepción ante cualquier intento de `update()` o `delete()`.
