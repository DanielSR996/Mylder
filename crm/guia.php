<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/guia.php";

$user = requireLogin();

$title = "Guía comercial";
$activeNav = "guia.php";
$pageSubtitle = "Speech, reglas y materiales de venta para el equipo";

$activeId = (int) ($_GET["id"] ?? 0);

ob_start();
?>
<div id="guiaApp">
  <p class="crm-empty">Cargando documentos…</p>
</div>

<?php if ($user["rol"] === "admin"): ?>
  <p class="crm-field-hint crm-mt-4">
    <a href="guia-admin.php" class="crm-link">Administrar documentos y subir PDFs →</a>
  </p>
<?php endif; ?>

<script>
const INITIAL_ID = <?= (int) $activeId ?>;

async function loadGuia() {
  const box = document.getElementById("guiaApp");
  let res;
  try {
    res = await fetch("api/guia-docs.php");
  } catch {
    box.innerHTML = "<div class=\"crm-card crm-p-6\"><p class=\"crm-empty\">No se pudo conectar con el servidor.</p></div>";
    return;
  }

  const data = await res.json();
  if (!data.ok) {
    box.innerHTML = "<div class=\"crm-card crm-p-6\"><p class=\"crm-empty\">Ejecuta la migración de guía: <code>php scripts/run-guia-migration.php</code></p></div>";
    return;
  }

  const docs = data.data || [];
  if (!docs.length) {
    box.innerHTML = "<div class=\"crm-card crm-p-6\"><p class=\"crm-empty\">Aún no hay documentos.<?php if ($user['rol'] === 'admin'): ?> <a href=\"guia-admin.php\" class=\"crm-link\">Sube el primer PDF</a>.<?php endif; ?></p></div>";
    return;
  }

  const pick = INITIAL_ID && docs.some(d => d.id == INITIAL_ID) ? INITIAL_ID : docs[0].id;
  renderGuia(docs, pick);
}

function renderGuia(docs, activeId) {
  const active = docs.find(d => d.id == activeId) || docs[0];
  const box = document.getElementById("guiaApp");

  const list = docs.map(d => `
    <li>
      <a href="guia.php?id=${d.id}" class="crm-doc-link ${d.id == active.id ? "is-active" : ""}">
        <span>${esc(d.nombre)}</span>
        <span class="crm-doc-link__meta">${d.tiene_pdf ? "PDF disponible" : "Sin PDF aún"}</span>
      </a>
    </li>
  `).join("");

  const preview = active.tiene_pdf
    ? `<div class="crm-pdf-viewer">
         <iframe src="${crmPdfViewerSrc(`guia-doc-pdf.php?id=${active.id}`)}" class="crm-pdf-frame crm-pdf-frame--clean" title="${esc(active.nombre)}"></iframe>
       </div>
       <p class="crm-field-hint" style="margin-top:0.65rem;text-align:right">
         <a href="guia-doc-pdf.php?id=${active.id}" target="_blank" rel="noopener" class="crm-link">Abrir en pestaña nueva ↗</a>
       </p>`
    : `<p class="crm-empty">Este documento aún no tiene PDF.<?php if ($user['rol'] === 'admin'): ?> Súbelo en <a href="guia-admin.php" class="crm-link">Administrar guía</a>.<?php endif; ?></p>`;

  box.innerHTML = `
    <div class="crm-grid-2 crm-guia-grid">
      <div class="crm-card crm-p-4">
        <h2 class="crm-card__title">Documentos</h2>
        <ul class="crm-doc-list">${list}</ul>
      </div>
      <div class="crm-card crm-pdf-only-card">
        ${preview}
      </div>
    </div>
  `;
}

function esc(s) { const d = document.createElement("div"); d.textContent = s ?? ""; return d.innerHTML; }
loadGuia();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . "/includes/layout.php";
