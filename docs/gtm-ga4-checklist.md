# GTM + GA4: checklist de conversiones Mylder

## 1) Requisitos previos
- Tener creado el contenedor de GTM `GTM-PR9NJLZC`.
- Tener propiedad de GA4 (Measurement ID tipo `G-XXXXXXX`).
- Publicar cambios de este repositorio para que `dataLayer.push` se dispare en producción.

## 2) Eventos ya instrumentados en frontend
Archivo: `main.js`

- `whatsapp_click`
  - `source`: `hero`, `floating_fab`, `footer`, etc.
- `booking_start`
  - `source`: `agenda_section`
- `booking_success`
  - `source`, `date`, `time`, `service`
- `contact_submit`
  - `status`: `success` o `fail`
  - `method`: `proxy`, `fallback_formsubmit`, `proxy_and_fallback`
  - `service`, `source`, `reason` (solo fail)
- `blog_open`
  - `blog_slug`

## 3) Configuración recomendada en GTM

### 3.1 Tag base GA4
1. Crear tag: **Google tag (GA4 Configuration)**.
2. Colocar Measurement ID.
3. Trigger: **All Pages**.

### 3.2 Variables de Data Layer
Crear variables (tipo Data Layer Variable):
- `dlv_source` -> `source`
- `dlv_status` -> `status`
- `dlv_method` -> `method`
- `dlv_service` -> `service`
- `dlv_reason` -> `reason`
- `dlv_blog_slug` -> `blog_slug`
- `dlv_date` -> `date`
- `dlv_time` -> `time`

### 3.3 Triggers de evento personalizado
Crear triggers tipo **Custom Event**:
- `ce_whatsapp_click` -> Event name: `whatsapp_click`
- `ce_booking_start` -> Event name: `booking_start`
- `ce_booking_success` -> Event name: `booking_success`
- `ce_contact_submit` -> Event name: `contact_submit`
- `ce_blog_open` -> Event name: `blog_open`

### 3.4 Tags de eventos GA4
Crear un tag GA4 Event por evento:

- `ga4_whatsapp_click`
  - Event name: `whatsapp_click`
  - Params: `source={{dlv_source}}`
  - Trigger: `ce_whatsapp_click`

- `ga4_booking_start`
  - Event name: `booking_start`
  - Params: `source={{dlv_source}}`
  - Trigger: `ce_booking_start`

- `ga4_booking_success`
  - Event name: `booking_success`
  - Params: `source`, `date`, `time`, `service`
  - Trigger: `ce_booking_success`

- `ga4_contact_submit`
  - Event name: `contact_submit`
  - Params: `status`, `method`, `service`, `source`, `reason`
  - Trigger: `ce_contact_submit`

- `ga4_blog_open`
  - Event name: `blog_open`
  - Params: `blog_slug`
  - Trigger: `ce_blog_open`

## 4) Conversiones en GA4
En GA4 > Admin > Events, marcar como conversiones:
- `whatsapp_click`
- `booking_start`
- `booking_success`
- `contact_submit`

Sugerencia: mantener `blog_open` como evento informativo (no conversión).

## 5) Validación antes de publicar
1. GTM Preview (Tag Assistant) en sitio real.
2. Disparar cada flujo:
   - clic WhatsApp
   - abrir modal de agenda
   - reservar cita
   - enviar contacto (success y fail)
   - abrir blog
3. Confirmar eventos en GA4 DebugView.
4. Publicar contenedor GTM.

## 6) Diagnóstico rápido de fallos
- Si no llega ningún evento: revisar tag base GA4.
- Si llega evento sin parámetros: revisar variables Data Layer.
- Si evento no dispara en GTM: revisar nombre exacto de Custom Event.
