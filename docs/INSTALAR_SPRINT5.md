# Instalación del Sprint 5 (Asistente virtual de IA en WhatsApp)

Historias: **HU17**, **HU18** y **HU20**. Agrega **dos migraciones** (`whatsapp_mensajes`, `whatsapp_conversaciones`)
y ninguna librería nueva (se usa el cliente HTTP de Laravel).

## Pasos

1. En la terminal de Laragon, dentro de `C:\laragon\www\sigedi`:
   ```
   php artisan migrate
   php artisan optimize:clear
   php artisan test
   ```
2. Agrega a `.env` las variables de `.env.example` (sección «Sprint 5») con tus credenciales de Meta y Gemini
   (ver `docs/CONFIGURAR_META_WHATSAPP.md`). **No subas `.env` a GitHub.**
3. En **Usuarios**, registra el número de WhatsApp del docente, del técnico y del administrador de demostración.
4. Prueba el asistente en la terminal con `php artisan whatsapp:simular` y luego con WhatsApp real
   (ver `docs/ASISTENTE_WHATSAPP.md`).

## Guion de la Sprint Review (con el celular)

| Paso | Quién escribe | Mensaje | Resultado esperado |
|---|---|---|---|
| 1 | Docente | `hola` | Saludo con su nombre y ejemplos |
| 2 | Docente | `¿cómo está la PC-LAB1-05?` | Estado real registrado en SIGEDI |
| 3 | Docente | `estado de PC-NO-EXISTE` | «No encontré…», sin inventar datos |
| 4 | Docente | `la impresora de la dirección no imprime` | Pide el código patrimonial |
| 5 | Docente | `IMP-DIR-01` | Muestra el equipo y pide SÍ/NO |
| 6 | Docente | `sí` | Devuelve el ticket INC-AAAA-NNN |
| 7 | Administrador (web) | Incidencias | La incidencia aparece con canal «whatsapp» y en la bitácora |
| 8 | Docente | `INC-AAAA-NNN` | Estado actual del ticket |
| 9 | Número no registrado | `hola` | Mensaje de número no registrado |
| 10 | Administrador (web) | Menú **Asistente** | Conexión configurada, indicadores y conversación |

## Capturas para el informe

Panel **Asistente** (conexión e indicadores), configuración del webhook en Meta (ocultando tokens), conversación de
consulta en el celular, conversación de reporte con el ticket, la incidencia creada por WhatsApp en el sistema web
y el resultado de `php artisan test`.

## Subir a GitHub

```
git add .
git commit -m "Sprint 5: asistente virtual de IA en WhatsApp (webhook, consultas y reporte de incidencias)"
git push
```
