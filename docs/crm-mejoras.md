# CRM Mylder — Mejoras implementadas y roadmap

## Fase 1 (implementada)

| Función | URL | Uso desde celular |
|---------|-----|-------------------|
| Modo llamada | `/crm/llamar.php` | Botón verde **Llamar** abre el marcador del teléfono |
| Ficha + historial | `/crm/prospecto.php?id=X` | Ver timeline de contactos |
| Búsqueda | Cola → buscar | Nombre, teléfono, ciudad |
| Panel Hoy | Dashboard | Callbacks y reuniones próximas |

La cola original (`queue.php`) sigue igual — nada se eliminó.

## Fase 2 (próxima, sin romper lo actual)

- Asignación masiva de prospectos al importar
- Campo "servicio de interés" al marcar interesado
- Reglas de reintento (máx. 3 intentos a no_contesta)
- Export CSV de interesados/convertidos

## Fase 3

- Webhook n8n al marcar interesado/reunión
- Leads del formulario web → CRM
- Métricas de embudo por categoría
