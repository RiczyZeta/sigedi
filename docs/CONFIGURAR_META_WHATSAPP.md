# Preparación del Sprint 5: aplicación de Meta y número de prueba de WhatsApp (riesgo R01)

Tarea técnica adelantada al Sprint 4 para que el Sprint 5 se dedique a programar el asistente.

1. Ingresa a <https://developers.facebook.com/> con una cuenta de Facebook y regístrate como desarrollador.
2. **Mis apps → Crear app** → caso de uso «Conectar con clientes por WhatsApp» (tipo *Business*). Nombre: `SIGEDI INA 54`.
3. En **WhatsApp → Configuración de la API** (API Setup):
   - Copia el **token de acceso temporal**, el **Phone number ID** y el **WhatsApp Business Account ID**.
   - En «Para», agrega y verifica tu número y el del Jefe de Talleres (el número de prueba solo envía a números registrados; máximo 5).
   - Envía el mensaje de plantilla `hello_world` para comprobar que llega al celular.
4. En **Configuración de la app → Básica**, copia la **clave secreta de la app** (servirá para validar la firma de Meta, RNF02).
5. En Google AI Studio (<https://aistudio.google.com/>) crea una **API key** de Gemini (capa gratuita).
6. Copia las claves al archivo `.env` (nunca al repositorio):
   ```
   WHATSAPP_TOKEN=...
   WHATSAPP_PHONE_NUMBER_ID=...
   WHATSAPP_BUSINESS_ACCOUNT_ID=...
   WHATSAPP_VERIFY_TOKEN=una_cadena_secreta_que_tu_inventas
   WHATSAPP_APP_SECRET=...
   GEMINI_API_KEY=...
   ```
   Revisa en el panel de Meta la versión vigente de la Graph API y en AI Studio el modelo Flash disponible,
   y ajústalos en `WHATSAPP_API_VERSION` y `GEMINI_MODEL` si difieren de los valores por defecto.
7. Ejecuta `php artisan config:clear`.

El token temporal vence en 24 horas; en el Sprint 5 se generará un token permanente con un **usuario del sistema**
(Business Manager → Usuarios del sistema) para que el webhook no deje de funcionar.

Evidencias para el informe: captura del panel «API Setup» (ocultando el token) y del mensaje `hello_world` recibido.
