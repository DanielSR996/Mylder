# Integracion n8n + WhatsApp (Mylder)

Esta guia conecta tu web con n8n usando el proxy `api/proxy.php`.

## 1) Configurar webhook de n8n en el proxy

En `api/settings.php` define:

- `N8N_WEBHOOK_URL`: URL del webhook de n8n (modo produccion).
- `N8N_WEBHOOK_SECRET`: secreto compartido para validar origen.
- `N8N_WEBHOOK_TIMEOUT_SECONDS`: timeout de envio (default 4).

Cuando `N8N_WEBHOOK_URL` esta vacio, no se envia nada a n8n.

## 2) Eventos que enviara la web

El proxy envia estos eventos a n8n:

- `contact_submitted`: cuando llega formulario de contacto.
- `booking_reserved`: cuando se reserva cita exitosamente.

Estructura general:

```json
{
  "eventType": "booking_reserved",
  "occurredAt": "2026-05-28T17:30:00Z",
  "source": "mylder_web_proxy",
  "requestMeta": {
    "ip": "200.0.0.1",
    "userAgent": "Mozilla/5.0 ..."
  },
  "data": {}
}
```

## 3) Workflow n8n recomendado (confirmacion y recordatorios)

Nodos sugeridos:

1. **Webhook** (POST)
2. **IF** por `eventType`
3. Rama `booking_reserved`:
   - Validar `data.lead.whatsappOptIn == true`
   - Enviar mensaje de confirmacion (WhatsApp Cloud API / Twilio)
   - Nodo `Wait` hasta 24h antes (si aplica)
   - Enviar recordatorio 24h antes
   - Nodo `Wait` hasta 2h antes
   - Enviar recordatorio 2h antes
4. Rama `contact_submitted`:
   - Notificacion interna a equipo (WhatsApp/Slack/Email)
   - Si `whatsappOptIn == true`, enviar acuse al cliente

## 4) Campos utiles para los mensajes

Para citas (`booking_reserved`):

- `data.lead.name`
- `data.lead.phone`
- `data.lead.service`
- `data.lead.whatsappOptIn`
- `data.booking.date`
- `data.booking.time`
- `data.booking.timezone`

Para contacto (`contact_submitted`):

- `data.lead.name`
- `data.lead.phone`
- `data.lead.email`
- `data.lead.service`
- `data.contact.message`

## 5) Seguridad recomendada en n8n

- Validar header `X-Mylder-Webhook-Secret`.
- Rechazar requests sin secreto correcto.
- Guardar logs de errores y reintentos.

## 6) Plantillas base de WhatsApp

Confirmacion de cita:

> Hola {{name}}, tu cita con Mylder quedo confirmada para el {{date}} a las {{time}}.  
> Si necesitas reprogramar, responde a este mensaje.

Recordatorio 24h:

> Recordatorio: tu cita es manana {{date}} a las {{time}}. Te esperamos.

Recordatorio 2h:

> Tu cita con Mylder es en 2 horas ({{time}}). Quedamos atentos.

## 7) JSON listos en este proyecto

Se agregaron estos archivos en `docs/n8n/`:

- `workflow-mylder-whatsapp.json` (workflow importable en n8n)
- `payload-contact_submitted.json` (ejemplo real del evento de formulario)
- `payload-booking_reserved.json` (ejemplo real del evento de cita)

## 8) Variables de entorno para el workflow

Configura estas variables en n8n antes de activar:

- `MYLDER_N8N_SECRET`: debe coincidir con `N8N_WEBHOOK_SECRET` del proxy.
- `WA_PHONE_NUMBER_ID`: id del numero de WhatsApp Business.
- `WA_TOKEN`: token de Meta WhatsApp Cloud API.
- `WA_INTERNAL_NUMBER`: numero interno para alertas de contacto.

## 9) Importar workflow en n8n

1. En n8n: **Workflows -> Import from file**.
2. Selecciona `docs/n8n/workflow-mylder-whatsapp.json`.
3. Ajusta el path del webhook si quieres otro endpoint.
4. Guarda y activa el workflow.

## 10) Mensaje y PDF por servicio (contacto)

El workflow ya incluye una secuencia para `contact_submitted`:

1. Mensaje de agradecimiento al cliente por WhatsApp.
2. Texto dinamico segun el servicio seleccionado en formulario.
3. Envio de PDF del servicio (si hay URL configurada).

Servicios contemplados en el mapeo:

- Landing Page
- Sitio Corporativo
- Desarrollo de software
- Apps moviles
- Automatiza tu negocio
- Marketing digital
- Branding
- SEO + Analytics
- Consultoria estrategica
- Paquete completo

Debes editar el nodo **Preparar Mensaje por Servicio** y reemplazar las URLs `https://example.com/...` por tus PDFs reales.

