<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/cotizaciones.php";

$user = requireLogin();

$title = "Cotizaciones";
$activeNav = "cotizaciones.php";
$pageSubtitle = "Precios, qué incluye cada servicio y PDFs para explicárselo al cliente";

$activeId = (int) ($_GET["id"] ?? 0);

ob_start();
?>
<div class="crm-cotiz-layout">
  <aside class="crm-card crm-p-4 crm-cotiz-list-wrap">
    <h2 class="crm-card__title">Servicios Mylder</h2>
    <div id="cotizList" class="crm-cotiz-list">
      <p class="crm-empty">Cargando…</p>
    </div>
    <?php if ($user["rol"] === "admin"): ?>
      <a href="cotizaciones-admin.php" class="crm-btn crm-btn-ghost crm-btn--block" style="margin-top:1rem">Administrar cotizaciones</a>
    <?php endif; ?>
  </aside>

  <section class="crm-cotiz-detail" id="cotizDetail">
    <div class="crm-card crm-p-5">
      <p class="crm-empty">Selecciona un servicio para ver precios, detalle y PDF.</p>
    </div>
  </section>
</div>

<script>
const INITIAL_ID = <?= (int) $activeId ?>;

async function loadList() {
  const res = await fetch("api/cotizaciones.php");
  const data = await res.json();
  const box = document.getElementById("cotizList");
  if (!data.ok || !data.data.length) {
    box.innerHTML = "<p class=\"crm-empty\">Sin cotizaciones disponibles.</p>";
    return;
  }

  box.innerHTML = data.data.map(c => `
    <button type="button" class="crm-cotiz-item" data-id="${c.id}">
      <span class="crm-cotiz-item__cat">${esc(c.categoria_label)}</span>
      <span class="crm-cotiz-item__name">${esc(c.nombre)}</span>
      <span class="crm-cotiz-item__price">${esc(c.precio_etiqueta || "Consultar")}</span>
      ${c.tiene_pdf ? '<span class="crm-cotiz-item__pdf">PDF ✓</span>' : '<span class="crm-cotiz-item__pdf crm-cotiz-item__pdf--none">Sin PDF</span>'}
    </button>
  `).join("");

  document.querySelectorAll(".crm-cotiz-item").forEach(btn => {
    btn.addEventListener("click", () => showDetail(Number(btn.dataset.id)));
  });

  const pick = INITIAL_ID || Number(data.data[0].id);
  showDetail(pick);
}

async function showDetail(id) {
  document.querySelectorAll(".crm-cotiz-item").forEach(el => {
    el.classList.toggle("is-active", Number(el.dataset.id) === id);
  });

  const res = await fetch("api/cotizaciones.php?id=" + id);
  const data = await res.json();
  const box = document.getElementById("cotizDetail");
  if (!data.ok) {
    box.innerHTML = "<div class=\"crm-card crm-p-5\"><p class=\"crm-empty\">No se pudo cargar.</p></div>";
    return;
  }

  const c = data.data;
  const pdfUrl = `cotizacion-pdf.php?id=${c.id}`;

  if (c.tiene_pdf) {
    box.innerHTML = `
      <div class="crm-card crm-pdf-only-card">
        <div class="crm-pdf-viewer">
          <iframe src="${crmPdfViewerSrc(pdfUrl)}" class="crm-pdf-frame crm-pdf-frame--clean" title="${esc(c.nombre)}"></iframe>
        </div>
        <p class="crm-field-hint">
          <a href="${pdfUrl}" target="_blank" rel="noopener" class="crm-link">Abrir en pestaña nueva ↗</a>
        </p>
      </div>
    `;
    return;
  }

  const incluye = (c.incluye_lista || []).map(i =>
    `<li>${esc(i)}</li>`
  ).join("");

  box.innerHTML = `
    <div class="crm-card crm-p-5">
      <div class="crm-cotiz-detail__head">
        <span class="crm-cotiz-item__cat">${esc(c.categoria_label)}</span>
        <h2 class="crm-cotiz-detail__title">${esc(c.nombre)}</h2>
        <p class="crm-cotiz-detail__resumen">${esc(c.resumen || "")}</p>
      </div>
      <div class="crm-cotiz-prices">
        <div class="crm-cotiz-price-box">
          <span class="crm-cotiz-price-box__label">Precio estándar</span>
          <span class="crm-cotiz-price-box__value">${esc(c.precio_etiqueta || "—")}</span>
        </div>
        ${c.precio_minimo ? `<div class="crm-cotiz-price-box crm-cotiz-price-box--min">
          <span class="crm-cotiz-price-box__label">Precio mínimo</span>
          <span class="crm-cotiz-price-box__value">${esc(c.precio_minimo)}</span>
        </div>` : ""}
      </div>
      ${c.descripcion ? `<p class="crm-cotiz-detail__desc">${esc(c.descripcion)}</p>` : ""}
      ${incluye ? `<h3 class="crm-card__title" style="margin-top:1.25rem">Qué incluye</h3><ul class="crm-cotiz-incluye">${incluye}</ul>` : ""}
      ${c.precio_nota ? `<div class="crm-alert crm-alert--dev" style="margin-top:1rem"><strong>Tip venta:</strong> ${esc(c.precio_nota)}</div>` : ""}
      ${c.comision_nota ? `<p class="crm-field-hint" style="margin-top:0.5rem"><strong>Tu comisión:</strong> ${esc(c.comision_nota)}</p>` : ""}
      <p class="crm-empty" style="margin-top:1.25rem">El administrador aún no subió el PDF para este servicio.</p>
    </div>
  `;
}

function esc(s) {
  const d = document.createElement("div");
  d.textContent = s ?? "";
  return d.innerHTML;
}

loadList();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . "/includes/layout.php";
