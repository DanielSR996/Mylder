<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/prospects.php";
$user = requireLogin();

$title = "Prospectos";
$activeNav = "queue.php";
$pageSubtitle = "Desde tu celular usa <a href=\"llamar.php\" id=\"linkModoLlamada\" class=\"crm-link\">Modo llamada</a> · aquí ves todos los negocios por contactar";

$catStmt = db()->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre");
$categorias = $catStmt->fetchAll();
$ciudades = prospectCiudadesList();
$estatusDefaultLabel = "Todos";

ob_start();
?>
<div class="crm-card crm-p-5 crm-mb-4">
  <div class="crm-field crm-mb-4">
    <label class="crm-label" for="searchQ">Buscar negocio</label>
    <div class="crm-form-row">
      <input type="search" id="searchQ" class="crm-input" placeholder="Nombre, teléfono o ciudad…" />
      <button type="button" id="btnSearch" class="crm-btn crm-btn-primary">Buscar</button>
    </div>
  </div>
  <div id="searchResults" class="crm-hidden crm-mb-4"></div>
  <div class="crm-filter-bar">
    <?php require __DIR__ . "/includes/prospect-filter-fields.php"; ?>
    <div class="crm-field">
      <button type="button" id="btnFiltrar" class="crm-btn crm-btn-primary crm-btn--block">Aplicar filtros</button>
    </div>
  </div>
</div>

<div class="crm-card">
  <div class="crm-table-wrap">
    <table class="crm-table">
      <thead>
        <tr>
          <th>Negocio</th>
          <th>Contacto</th>
          <th>Categoría</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="prospectsBody">
        <tr><td colspan="5" class="crm-empty" style="text-align:center;padding:2rem">Cargando…</td></tr>
      </tbody>
    </table>
  </div>
  <div class="crm-table-footer">
    <span id="paginationInfo" class="crm-table-footer__info"></span>
    <div style="display:flex;gap:0.5rem">
      <button type="button" id="btnPrev" class="crm-btn crm-btn-ghost crm-btn--sm" disabled>Anterior</button>
      <button type="button" id="btnNext" class="crm-btn crm-btn-ghost crm-btn--sm" disabled>Siguiente</button>
    </div>
  </div>
</div>

<div id="markModal" class="crm-modal-backdrop" role="dialog" aria-modal="true">
  <div class="crm-modal">
    <h2 class="crm-modal__title" id="modalNombre"></h2>
    <p class="crm-modal__sub" id="modalTelefono"></p>

    <div class="crm-field crm-mb-4">
      <label class="crm-label" for="modalResultado">Resultado</label>
      <select id="modalResultado" class="crm-select">
        <?php foreach (PROSPECTO_ESTADOS as $key => $label): ?>
          <?php if ($key === "pendiente") continue; ?>
          <option value="<?= authEsc($key) ?>"><?= authEsc($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div id="fieldCallback" class="crm-field crm-mb-4 crm-hidden">
      <label class="crm-label" for="modalCallback">Fecha callback *</label>
      <input type="datetime-local" id="modalCallback" class="crm-input" />
    </div>

    <div id="fieldReunion" class="crm-hidden">
      <div class="crm-field crm-mb-4">
        <label class="crm-label" for="modalReunion">Fecha reunión *</label>
        <input type="datetime-local" id="modalReunion" class="crm-input" />
      </div>
      <div class="crm-field crm-mb-4">
        <label class="crm-label" for="modalLinkReunion">Link reunión (Meet/Zoom)</label>
        <input type="url" id="modalLinkReunion" placeholder="https://..." class="crm-input" />
      </div>
    </div>

    <div id="modalBitacoraWrap" class="crm-mb-4 crm-hidden">
      <p class="crm-label">Bitácora</p>
      <div id="modalBitacora"></div>
    </div>

    <div class="crm-field crm-mb-4">
      <label class="crm-label" for="modalNotas">Agregar nota a la bitácora</label>
      <textarea id="modalNotas" rows="3" class="crm-textarea" placeholder="Qué pasó, qué mejorar, acuerdos…"></textarea>
    </div>

    <div class="crm-modal__actions">
      <button type="button" id="modalCancel" class="crm-btn crm-btn-ghost">Cancelar</button>
      <button type="button" id="modalSave" class="crm-btn crm-btn-accent">Guardar</button>
    </div>
  </div>
</div>

<script src="assets/prospect-filters.js?v=20260620"></script>
<script src="assets/bitacora.js?v=20260620"></script>
<script>
const ESTADOS = <?= json_encode(PROSPECTO_ESTADOS, JSON_UNESCAPED_UNICODE) ?>;
let currentPage = 1;
let selectedProspect = null;
let activeFilters = crmProspectFilters.initForm();

const modal = document.getElementById("markModal");
document.body.appendChild(modal);

const modalResultado = document.getElementById("modalResultado");
const fieldCallback = document.getElementById("fieldCallback");
const fieldReunion = document.getElementById("fieldReunion");

function organicBadge(origen) {
  if (origen === "web_contacto") return '<span class="crm-badge-organic">Orgánico · Web</span>';
  if (origen === "web_cita") return '<span class="crm-badge-organic">Orgánico · Cita</span>';
  return "";
}

function organicContextHtml(p) {
  if (p.origen !== "web_contacto" && p.origen !== "web_cita") return "";
  const parts = [];
  if (p.email) parts.push(`<strong>Email:</strong> ${esc(p.email)}`);
  if (p.servicio_interes) parts.push(`<strong>Servicio:</strong> ${esc(p.servicio_interes)}`);
  if (p.mensaje_web) {
    const msg = p.mensaje_web.length > 160 ? p.mensaje_web.slice(0, 160) + "…" : p.mensaje_web;
    parts.push(`<strong>Mensaje:</strong> ${esc(msg)}`);
  }
  if (!parts.length) return "";
  return `<div class="crm-organic-context-snippet">${parts.join("<br>")}</div>`;
}

function statusBadge(estado) {
  const label = ESTADOS[estado] || estado;
  return `<span class="status-badge status-${estado}">${label}</span>`;
}

function toggleExtraFields() {
  const val = modalResultado.value;
  fieldCallback.classList.toggle("crm-hidden", val !== "pensando");
  fieldReunion.classList.toggle("crm-hidden", val !== "reunion_agendada");
}

modalResultado.addEventListener("change", toggleExtraFields);

async function loadProspects(page = 1) {
  currentPage = page;
  activeFilters = crmProspectFilters.readForm();
  crmProspectFilters.save(activeFilters);
  syncLlamadaLink();

  const params = crmProspectFilters.toSearchParams(activeFilters);
  params.set("page", String(page));
  params.set("per_page", "25");

  const res = await fetch(`api/prospects.php?${params}`);
  const data = await res.json();

  const tbody = document.getElementById("prospectsBody");
  if (!data.ok) {
    tbody.innerHTML = `<tr><td colspan="5" class="crm-empty" style="text-align:center;padding:2.5rem">Error: ${esc(data.error || "No se pudo cargar la lista")}</td></tr>`;
    document.getElementById("paginationInfo").textContent = "";
    document.getElementById("btnPrev").disabled = true;
    document.getElementById("btnNext").disabled = true;
    return;
  }
  if (!data.data.length) {
    tbody.innerHTML = `<tr><td colspan="5" class="crm-empty" style="text-align:center;padding:2.5rem">Sin prospectos con estos filtros.</td></tr>`;
    document.getElementById("paginationInfo").textContent = "";
    document.getElementById("btnPrev").disabled = true;
    document.getElementById("btnNext").disabled = true;
    return;
  }

  tbody.innerHTML = data.data.map(p => `
    <tr>
      <td>
        <a href="prospecto.php?id=${p.id}" class="crm-cell-title crm-link">${esc(p.nombre)}</a>
        <div class="crm-cell-meta">${esc(p.ciudad || "—")}</div>
        ${organicBadge(p.origen)}
        ${organicContextHtml(p)}
        ${p.notas && String(p.notas).trim() ? `<div class="crm-bitacora-snippet" title="Bitácora">${esc(String(p.notas).trim().length > 140 ? String(p.notas).trim().slice(0, 140) + "…" : String(p.notas).trim())}</div>` : ""}
        ${p.calificacion ? `<div class="crm-cell-meta">★ ${p.calificacion} (${p.num_resenas || 0} reseñas)</div>` : ""}
      </td>
      <td>
        <a href="tel:${esc(p.telefono)}" class="crm-link">${esc(p.telefono)}</a>
        ${p.link_google_maps ? `<div><a href="${esc(p.link_google_maps)}" target="_blank" rel="noopener" class="crm-link crm-link--maps">Ver en Maps ↗</a></div>` : ""}
      </td>
      <td><span class="crm-list-row__name">${esc(p.categoria_nombre)}</span></td>
      <td>
        ${statusBadge(p.estado)}
        ${p.intentos_sin_respuesta > 0 ? `<div class="crm-cell-meta">Sin respuesta: ${p.intentos_sin_respuesta}/3</div>` : ""}
        ${p.intentos > 0 ? `<div class="crm-cell-meta">${p.intentos} intento${p.intentos === 1 ? "" : "s"} en bitácora</div>` : ""}
        ${p.ultimo_agente_nombre ? `<div class="crm-cell-meta">Último: ${esc(p.ultimo_agente_nombre)}</div>` : ""}
        ${p.fecha_callback ? `<div class="crm-cell-meta">Callback: ${esc(p.fecha_callback)}</div>` : ""}
        ${p.fecha_reunion ? `<div class="crm-cell-meta">Reunión: ${esc(p.fecha_reunion)}</div>` : ""}
      </td>
      <td>
        <button type="button" class="crm-btn crm-btn-accent crm-btn--sm mark-btn" data-id="${p.id}" data-nombre="${escAttr(p.nombre)}" data-telefono="${escAttr(p.telefono)}">Marcar</button>
      </td>
    </tr>
  `).join("");

  document.querySelectorAll(".mark-btn").forEach(btn => {
    btn.addEventListener("click", () => openModal({
      id: btn.getAttribute("data-id"),
      nombre: btn.getAttribute("data-nombre"),
      telefono: btn.getAttribute("data-telefono"),
    }));
  });

  const pg = data.pagination;
  document.getElementById("paginationInfo").textContent = `Página ${pg.page} de ${pg.pages} · ${pg.total} total`;
  document.getElementById("btnPrev").disabled = pg.page <= 1;
  document.getElementById("btnNext").disabled = pg.page >= pg.pages;
}

async function openModal(dataset) {
  selectedProspect = dataset.id;
  document.getElementById("modalNombre").textContent = dataset.nombre || "";
  document.getElementById("modalTelefono").textContent = dataset.telefono || "";
  document.getElementById("modalNotas").value = "";
  document.getElementById("modalCallback").value = "";
  document.getElementById("modalReunion").value = "";
  document.getElementById("modalLinkReunion").value = "";
  modalResultado.value = "no_contesta";
  toggleExtraFields();

  const bitWrap = document.getElementById("modalBitacoraWrap");
  const bitEl = document.getElementById("modalBitacora");
  bitWrap.classList.add("crm-hidden");
  bitEl.innerHTML = '<p class="crm-bitacora-box__empty">Cargando bitácora…</p>';

  modal.classList.add("is-open");
  document.body.classList.add("crm-modal-open");

  try {
    const res = await fetch(`api/prospects.php?id=${encodeURIComponent(dataset.id)}`);
    const data = await res.json();
    if (data.ok && crmBitacora.hasContent(data.prospecto?.notas, data.historial)) {
      bitEl.innerHTML = crmBitacora.html(data.prospecto.notas, data.historial, ESTADOS, { compact: true, maxItems: 6 });
      bitWrap.classList.remove("crm-hidden");
    } else {
      bitWrap.classList.add("crm-hidden");
      bitEl.innerHTML = "";
    }
  } catch {
    bitWrap.classList.add("crm-hidden");
    bitEl.innerHTML = "";
  }
}

function closeModal() {
  modal.classList.remove("is-open");
  document.body.classList.remove("crm-modal-open");
  selectedProspect = null;
}

document.getElementById("modalCancel").addEventListener("click", closeModal);
modal.addEventListener("click", (e) => { if (e.target === modal) closeModal(); });
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && modal.classList.contains("is-open")) closeModal();
});
function syncLlamadaLink() {
  const link = document.getElementById("linkModoLlamada");
  if (!link) return;
  const q = crmProspectFilters.toSearchParams(activeFilters).toString();
  link.href = q ? `llamar.php?${q}` : "llamar.php";
}

document.getElementById("btnFiltrar").addEventListener("click", () => {
  document.getElementById("searchResults").classList.add("crm-hidden");
  loadProspects(1);
});

document.getElementById("btnSearch").addEventListener("click", doSearch);
document.getElementById("searchQ").addEventListener("keydown", (e) => {
  if (e.key === "Enter") doSearch();
});

async function doSearch() {
  const q = document.getElementById("searchQ").value.trim();
  const box = document.getElementById("searchResults");
  if (!q) {
    box.classList.add("crm-hidden");
    loadProspects(1);
    return;
  }
  const res = await fetch(`api/prospects.php?q=${encodeURIComponent(q)}`);
  const data = await res.json();
  box.classList.remove("crm-hidden");
  if (!data.ok || !data.data.length) {
    box.innerHTML = '<p class="crm-empty">Sin resultados para «' + esc(q) + '»</p>';
    return;
  }
  box.innerHTML = data.data.map(p =>
    `<a href="prospecto.php?id=${p.id}" class="crm-hoy-row">
      <div><div class="crm-list-row__name">${esc(p.nombre)}</div><div class="crm-list-row__meta">${esc(p.telefono)} · ${esc(p.categoria_nombre)}</div></div>
      <span class="crm-hoy-row__action">Ver →</span>
    </a>`
  ).join("");
}

document.getElementById("btnPrev").addEventListener("click", () => loadProspects(currentPage - 1));
document.getElementById("btnNext").addEventListener("click", () => loadProspects(currentPage + 1));

document.getElementById("modalSave").addEventListener("click", async () => {
  if (!selectedProspect) return;
  const payload = {
    prospecto_id: Number(selectedProspect),
    resultado: modalResultado.value,
    notas: document.getElementById("modalNotas").value.trim(),
    fecha_callback: document.getElementById("modalCallback").value,
    fecha_reunion: document.getElementById("modalReunion").value,
    link_reunion: document.getElementById("modalLinkReunion").value.trim(),
  };

  const res = await fetch("api/prospects.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  if (data.ok) {
    const msg = data.oculto
      ? "Guardado. Prospecto oculto tras 3 intentos sin respuesta."
      : "Guardado · " + (data.agente || "agente");
    crmToast(msg);
    closeModal();
    loadProspects(currentPage);
  } else {
    alert(data.error || "Error al guardar");
  }
});

function esc(s) {
  const d = document.createElement("div");
  d.textContent = s ?? "";
  return d.innerHTML;
}
function escAttr(s) {
  return String(s ?? "").replace(/"/g, "&quot;");
}

syncLlamadaLink();
loadProspects(1);
</script>
<?php
$content = ob_get_clean();
require __DIR__ . "/includes/layout.php";
