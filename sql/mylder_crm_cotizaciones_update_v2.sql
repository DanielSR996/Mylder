-- Actualiza catálogo a 8 cotizaciones (2026)
-- Ejecutar: php scripts/run-cotizaciones-update-v2.php

UPDATE cotizaciones SET activo = 0 WHERE slug IN (
  'sitio-web-landing',
  'tienda-en-linea',
  'automatizacion-esencial',
  'automatizacion-respuesta',
  'automatizacion-inteligente',
  'soporte-web-starter',
  'soporte-web-pro',
  'manual-ventas'
);

INSERT INTO cotizaciones (nombre, slug, categoria, resumen, descripcion, incluye, precio_etiqueta, precio_minimo, precio_nota, comision_nota, orden) VALUES
(
  'Sitio Web',
  'sitio-web',
  'sitio',
  'Presencia digital a código con desglose justificado y anticipo 50/50.',
  'Para negocios que quieren un sitio único, con su branding, que convierta visitas en clientes. El PDF incluye 10 líneas de desglose, tabla de qué incluye, anticipo/saldo 50/50 y qué necesitamos del cliente para arrancar.',
  'Diseño 100% a código (sin plantillas)|SEO básico desde el día 1|Dominio y hosting primer año|Responsive móvil/tablet/desktop|Hasta 5 correos corporativos|Entrega 10-15 días hábiles|Anticipo 50% para iniciar|Saldo 50% al entregar',
  '$9,500 MXN',
  NULL,
  'Anticipo 50% para arrancar, saldo 50% al entregar. Pide logo, textos, fotos y accesos antes de iniciar.',
  NULL,
  10
),
(
  'Mantenimiento Anual',
  'mantenimiento-anual',
  'soporte',
  'Hosting, dominio, SSL y soporte técnico — pago único anual.',
  'Explica por qué el mantenimiento es necesario a partir del segundo año. El PDF desglosa 7 conceptos (hosting, dominio, SSL, respaldos, actualizaciones, soporte, monitoreo).',
  'Renovación de hosting|Renovación de dominio|Certificado SSL|Respaldos periódicos|Actualizaciones de seguridad|Soporte técnico|Monitoreo básico',
  '$2,000 MXN / año',
  NULL,
  'Pago único anual. No es opcional si quieren que su sitio siga en línea sin problemas.',
  NULL,
  20
),
(
  'Menú Digital',
  'menu-digital',
  'sitio',
  'Menú QR interactivo — alternativa inteligente al menú impreso.',
  'Incluye el argumento de venta vs menús impresos (costo de reimpresión, cambios de precios, higiene). Desglose de 5 conceptos. Nota de descuento si contratan también el sitio web.',
  'Diseño acorde al branding|Menú editable por el cliente|Código QR para mesas|Compatible móvil|Actualizaciones sin reimprimir',
  '$4,205 MXN',
  NULL,
  'Argumento clave: cada cambio de precio en impreso cuesta; el digital se actualiza al instante. Menciona descuento en paquete con sitio web.',
  NULL,
  30
),
(
  'Branding',
  'branding',
  'branding',
  'Identidad visual completa: logo, paleta, tipografía y manual de marca.',
  'Detalla qué se construye exactamente: logo principal y versiones, paleta de colores, tipografía, manual de marca, papelería básica y archivos editables. Desglose de 8 conceptos. Nota de paquete combinado con sitio web.',
  'Logotipo principal|Versiones del logo (horizontal, icono)|Paleta de colores|Tipografía corporativa|Manual de identidad|Papelería básica|Archivos editables (AI, PNG, SVG)|Guía de uso de marca',
  '$6,728 MXN',
  NULL,
  'Si no tienen logo, esto no es un problema — es oportunidad. Ofrece paquete combinado con sitio web.',
  NULL,
  40
),
(
  'E-commerce',
  'ecommerce',
  'ecommerce',
  'Tienda en línea — 3 planes: Chico, Mediano y Grande.',
  'Un solo documento con 3 planes. Chico $13,792 · Mediano $24,220 · Grande $43,227. Cada plan con su propio desglose y esquema de anticipo.',
  'Chico (hasta ~30 productos): $13,792 MXN|Mediano (30-100 productos): $24,220 MXN|Grande (100+ productos): $43,227 MXN|Catálogo con fotos y descripciones|Pasarela de pago en línea|Panel de administración|Capacitación básica',
  'Desde $13,792 MXN',
  NULL,
  'NO des un solo precio en la llamada — pregunta cuántos productos, si cobran en línea y si manejan inventario. El PDF tiene los 3 planes con anticipo por nivel.',
  NULL,
  50
),
(
  'Automatización con IA',
  'automatizacion-ia',
  'automatizacion',
  'Setup $3,700 + planes mensuales Esencial, Respuesta e Inteligente.',
  'Setup único $3,700 MXN. Planes: Esencial $1,800/mes · Respuesta $3,500/mes · Inteligente $5,500/mes. Incluye comparativa integrada vs contratar community manager.',
  'Setup inicial (configuración): $3,700 MXN|Esencial: recordatorios y confirmación — $1,800/mes|Respuesta: IA básica + captura prospectos — $3,500/mes|Inteligente: IA avanzada + seguimiento — $5,500/mes|Comparativa vs community manager|Reportes periódicos',
  'Setup $3,700 + desde $1,800/mes',
  NULL,
  'Siempre cobra el setup. Argumento vs CM: responde al instante, 24/7, sin sueldo ni vacaciones. Prioriza plan Inteligente para ingreso recurrente.',
  NULL,
  60
),
(
  'Remodelación de Sitio',
  'remodelacion',
  'sitio',
  'Moderniza un sitio existente sin empezar desde cero.',
  'Incluye señales de que el sitio necesita remodelación, auditoría técnica, migración de contenido, redirecciones SEO y tabla comparativa vs sitio nuevo desde cero.',
  'Auditoría del sitio actual|Rediseño visual moderno|Migración de contenido|Redirecciones 301 (SEO)|Optimización móvil|Mejora de velocidad|Capacitación de uso',
  '$9,755 MXN',
  NULL,
  'Ideal cuando ya tienen sitio pero se ve viejo, no carga en móvil o no aparecen en Google. Compara con sitio desde cero en el PDF.',
  NULL,
  70
),
(
  'Equipo Web Mensual',
  'equipo-web-mensual',
  'soporte',
  'Starter $2,500/mes · Pro $4,500/mes — exclusivo Querétaro.',
  'Dos planes en un documento con desglose individual. Solo disponible en Querétaro. Comparativa vs contratar nómina interna (diseñador + desarrollador).',
  'Starter: soporte web esencial — $2,500/mes|Pro: soporte avanzado + cambios frecuentes — $4,500/mes|Solo Querétaro y zona metropolitana|Comparativa vs nómina interna|Sin contratos de personal|Respuesta ágil',
  'Desde $2,500 / mes',
  NULL,
  'Exclusivo Querétaro — no prometas fuera de la zona. Argumento: un diseñador + dev cuestan $25,000+/mes en nómina.',
  NULL,
  80
)
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  categoria = VALUES(categoria),
  resumen = VALUES(resumen),
  descripcion = VALUES(descripcion),
  incluye = VALUES(incluye),
  precio_etiqueta = VALUES(precio_etiqueta),
  precio_minimo = VALUES(precio_minimo),
  precio_nota = VALUES(precio_nota),
  comision_nota = VALUES(comision_nota),
  orden = VALUES(orden),
  activo = 1;
