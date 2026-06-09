# Search Console + SEO operativo (Mylder)

## 1) Estado técnico actual
- `robots.txt` publicado con `Sitemap: https://mylder.mx/sitemap.xml`
- `sitemap.xml` creado con URLs principales.
- Canonical configurado en páginas clave.
- Metadatos OG/Twitter y datos estructurados base habilitados.

## 2) Alta en Google Search Console

### Opción recomendada: propiedad de dominio
1. Abrir Search Console.
2. Añadir propiedad tipo **Dominio**: `mylder.mx`.
3. Copiar registro TXT.
4. Agregar TXT en DNS del dominio.
5. Verificar propiedad.

### Opción alterna: URL prefix
1. Añadir propiedad `https://mylder.mx/`.
2. Verificar con meta tag o archivo HTML.
3. Si usas meta tag, colocarlo en `index.html` y publicar.

## 3) Envío de sitemap
1. Search Console > Sitemaps.
2. Enviar: `https://mylder.mx/sitemap.xml`.
3. Confirmar estado `Success`.

## 4) Inspección e indexación inicial
Solicitar indexación para:
- Home: `https://mylder.mx/`
- Blogs:
  - `https://mylder.mx/blog-experiencia-inmuebles-digital`
  - `https://mylder.mx/blog-importancia-digitalizar-empresa`
  - `https://mylder.mx/blog-importancia-consultores-empresa`
- Legales:
  - `https://mylder.mx/terminos-condiciones`
  - `https://mylder.mx/aviso-privacidad`

## 5) Evitar conflicto `.html` vs URL limpia
- Mantener una sola versión canónica (sin `.html`).
- Si el hosting lo permite, configurar redirección 301 de `*.html` hacia URL limpia.
- Confirmar en Search Console que no aparezcan duplicados `Duplicate without user-selected canonical`.

## 6) Revisión semanal (operación)
- Coverage/Pages:
  - `Crawled - currently not indexed`
  - `Duplicate without user-selected canonical`
  - `Soft 404`
- Search results:
  - consultas top
  - CTR por URL
  - evolución de impresiones

## 7) Acciones de mejora SEO continuas
- Publicar mínimo 1 blog nuevo por mes.
- Enlazar blogs entre sí y desde home.
- Actualizar fechas de contenidos cuando haya cambios reales.
- Optimizar imágenes en `webp/avif` para mejorar LCP.

