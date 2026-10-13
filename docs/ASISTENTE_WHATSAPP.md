# SIGEDI – Asistente virtual de WhatsApp (Sprint 5)

Historias: **HU17** (conexión con WhatsApp Business Cloud API), **HU18** (consultas en lenguaje natural) y
**HU20** (reporte de incidencias con número de ticket).

## Arquitectura

```
Usuario (WhatsApp) ──► Meta Cloud API ──► POST /webhook/whatsapp
                                              │ VerificarFirmaWhatsApp (X-Hub-Signature-256, RNF02)
                                              ▼
                                    WebhookWhatsAppController
                                              ▼
                                      AsistenteWhatsApp
                ┌─────────────────────────────┼──────────────────────────────┐
                ▼                             ▼                              ▼
     Identifica al usuario          Interprete (reglas + Gemini)      Conversación (HU20)
     por su número registrado       intención y códigos validados     pedir código → descripción
                                    contra la base de datos           → confirmar → registrar
                                              ▼
                       Respuesta armada SOLO con datos de MySQL (RNF08)
                                              ▼
                     WhatsAppCliente ──► Meta Cloud API ──► Usuario
```

- **Gemini solo clasifica**: devuelve la intención, los códigos mencionados, la descripción y la categoría de la falla.
  Los códigos se validan contra la base de datos y la respuesta se arma con los datos registrados, así que el asistente
  no puede inventar estados ni incidencias (RNF08, riesgo R03).
- **A Gemini solo se envía el texto del mensaje** (máx. 1000 caracteres): nunca nombres, correos ni números (riesgo R08).
- **Sin Gemini** (sin API key, sin cuota o sin conexión) el asistente sigue funcionando con reglas de palabras clave.
- Cada mensaje de Meta se procesa **una sola vez** (`wa_message_id` único): Meta reintenta los webhooks.
- Las respuestas se envían dentro de la **ventana de 24 horas** que abre el usuario al escribir: no hay costo.

## Intenciones

| Intención | Ejemplo | Respuesta |
|---|---|---|
| saludo / ayuda | «hola», «ayuda» | Saludo con el nombre y ejemplos de uso |
| consultar_dispositivo | «estado de PC-LAB1-05» | Tipo, ubicación, estado y n.° de incidencias abiertas |
| consultar_incidencia | «INC-2026-007» | Estado, prioridad, técnico, fecha y solución (si existe). El usuario solicitante solo ve las suyas |
| mis_incidencias | «mis incidencias» | Hasta 5 incidencias abiertas (las reportadas o, para el técnico, las asignadas) |
| reportar_incidencia | «la impresora IMP-DIR-01 no imprime» | Flujo de reporte con confirmación |
| desconocida | — | «No entendí tu mensaje» + ejemplos |

## Flujo de reporte (HU20)

1. El usuario describe la falla. Si no indica el código patrimonial, el asistente lo pide.
2. Si la descripción es muy corta, pide que explique la falla.
3. Muestra equipo, ubicación, categoría y falla, y pide **SÍ** o **NO** (criterio: «confirma el dispositivo antes de registrar»).
4. Con **SÍ** crea la incidencia con canal `whatsapp`, impacto y urgencia medios (el técnico puede reclasificarla) y
   devuelve el ticket `INC-AAAA-NNN`. La creación queda en la bitácora con el usuario que escribió.
5. **NO** o `cancelar` anulan el reporte. La conversación vence a los 30 minutos sin respuesta.

No se aceptan equipos dados de baja ni códigos inexistentes.

## Probar sin WhatsApp (en la terminal de Laragon)

Registra tu número en **Usuarios** (por ejemplo `+51987654321`) y ejecuta:

```
php artisan whatsapp:simular 51987654321 hola
php artisan whatsapp:simular 51987654321 "estado de PC-LAB1-05"
php artisan whatsapp:simular 51987654321 "la impresora IMP-DIR-01 no imprime, sale la hoja en blanco"
php artisan whatsapp:simular 51987654321 si
```

El comando no envía nada a Meta; la conversación y los mensajes sí quedan registrados en el panel **Asistente**.

## Conectar con WhatsApp real

1. Completa `.env` según `docs/CONFIGURAR_META_WHATSAPP.md` y ejecuta `php artisan config:clear`.
2. Prueba el envío: `php artisan whatsapp:probar 51987654321` (antes, escribe tú al número de prueba para abrir la ventana de 24 h).
3. Publica el sitio local con HTTPS mediante Cloudflare Tunnel (gratuito, sin cuenta):
   ```
   cloudflared tunnel --url http://localhost:80 --http-host-header sigedi.test
   ```
   Copia la URL `https://....trycloudflare.com` que aparece.
4. En Meta for Developers → **WhatsApp → Configuración → Webhook**:
   - URL de devolución de llamada: `https://....trycloudflare.com/webhook/whatsapp`
   - Token de verificación: el valor de `WHATSAPP_VERIFY_TOKEN`
   - Pulsa **Verificar y guardar** y suscríbete al campo **messages**.
5. Escribe al número de prueba desde un celular registrado en SIGEDI.

La URL de `trycloudflare.com` cambia cada vez que se reinicia el túnel: vuelve a registrarla en Meta. En el cierre del
proyecto se usará un túnel con nombre o el servidor definitivo (riesgo R05).

### Problemas frecuentes

| Síntoma | Solución |
|---|---|
| `cURL error 60: SSL certificate problem` | Descarga `cacert.pem` (curl.se/docs/caextract.html) y en `php.ini` de Laragon define `curl.cainfo` y `openssl.cafile` con su ruta; reinicia Laragon. |
| Meta no verifica el webhook | Revisa que `WHATSAPP_VERIFY_TOKEN` coincida y que el túnel esté activo. |
| El webhook responde 403 | Falta o no coincide `WHATSAPP_APP_SECRET` (clave secreta de la app). |
| No llega la respuesta | El token temporal venció (24 h) o el número no está en la lista de destinatarios de prueba. Revisa «Errores de envío» en el panel. |
| Responde «número no registrado» | Registra el número en Usuarios con el formato `+51987654321`. |
