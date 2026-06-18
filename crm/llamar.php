<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/prospects.php";
$user = requireLogin();

$title = "Modo llamada";
$activeNav = "llamar.php";
$pageSubtitle = "Llama y marca resultado · los filtros coinciden con la lista de Prospectos";

$catStmt = db()->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre");
$categorias = $catStmt->fetchAll();
$ciudades = prospectCiudadesList();
$estatusDefaultLabel = "Cola activa";

ob_start();
?>
<div class="crm-card crm-p-5 crm-mb-4">
  <details class="crm-filter-details" id="filterDetails" open>
    <summary class="crm-filter-details__summary">Filtros de llamada</summary>
    <p class="crm-card__hint crm-mb-4" id="filterSummary"></p>
    <div class="crm-filter-bar">
      <?php require __DIR__ . "/includes/prospect-filter-fields.php"; ?>
      <div class="crm-field">
        <button type="button" id="btnFiltrar" class="crm-btn crm-btn-primary crm-btn--block">Aplicar y cargar siguiente</button>
      </div>
    </div>
  </details>
</div>

<div id="dialerApp">
  <div id="dialerLoading" class="crm-dialer-empty">
    <div class="crm-skeleton" style="height:8rem;border-radius:18px;margin-bottom:1rem"></div>
    <p class="crm-empty">Cargando siguiente prospecto…</p>
  </div>

  <div id="dialerEmpty" class="crm-dialer-empty crm-hidden">
    <div class="crm-card crm-p-5" style="text-align:center">
      <p class="crm-card__title" style="margin-bottom:0.5rem">Sin prospectos con estos filtros</p>
      <p class="crm-empty">Prueba otro estado o categoría, o quita filtros para ver la cola activa.</p>
      <a href="queue.php" class="crm-btn crm-btn-primary" style="margin-top:1rem">Ver todos los prospectos</a>
    </div>
  </div>

  <div id="dialerCard" class="crm-hidden">
    <div class="crm-dialer-card crm-card">
      <div class="crm-dialer-card__top">
        <span id="dCat" class="crm-dialer-badge"></span>
        <span id="dOrganic" class="crm-badge-organic crm-hidden"></span>
        <span id="dEstado" class="status-badge"></span>
      </div>
      <h2 id="dNombre" class="crm-dialer-name"></h2>
      <p id="dCiudad" class="crm-dialer-meta"></p>
      <p id="dRating" class="crm-dialer-meta"></p>

      <div id="dWebContext" class="crm-organic-context-box crm-hidden"></div>

      <div id="dBitacoraWrap" class="crm-hidden">
        <p class="crm-bitacora-box__heading">Bitácora</p>
        <div id="dBitacora"></div>
      </div>

      <a id="dTelBtn" href="#" class="crm-dialer-call">
        <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
        <span id="dTelText"></span>
      </a>

      <div class="crm-dialer-actions-row">
        <a id="dWaBtn" href="#" target="_blank" rel="noopener" class="crm-btn crm-btn-ghost crm-btn--block">WhatsApp</a>
        <a id="dMapsBtn" href="#" target="_blank" rel="noopener" class="crm-btn crm-btn-ghost crm-btn--block crm-hidden">Maps</a>
        <a id="dFichaBtn" href="#" class="crm-btn crm-btn-ghost crm-btn--block">Ver ficha</a>
      </div>
    </div>

    <div class="crm-card crm-p-5 crm-mt-4">
      <h3 class="crm-card__title">Resultado de la llamada</h3>
      <div class="crm-dialer-results" id="quickResults"></div>

      <div id="extraFields" class="crm-hidden" style="margin-top:1rem">
        <div id="wrapCallback" class="crm-field crm-mb-4 crm-hidden">
          <label class="crm-label" for="dCallback">Fecha callback *</label>
          <input type="datetime-local" id="dCallback" class="crm-input" />
        </div>
        <div id="wrapReunion" class="crm-hidden">
          <div class="crm-field crm-mb-4">
            <label class="crm-label" for="dReunion">Fecha reunión *</label>
            <input type="datetime-local" id="dReunion" class="crm-input" />
          </div>
          <div class="crm-field crm-mb-4">
            <label class="crm-label" for="dLink">Link reunión</label>
            <input type="url" id="dLink" class="crm-input" placeholder="https://meet.google.com/..." />
          </div>
        </div>
        <div class="crm-field crm-mb-4">
          <label class="crm-label" for="dNotas">Agregar nota a la bitácora</label>
          <textarea id="dNotas" class="crm-textarea" rows="2" placeholder="Qué pasó, qué mejorar, acuerdos…"></textarea>
        </div>
      </div>

      <button type="button" id="btnSaveNext" class="crm-btn crm-btn-accent crm-btn--block" style="margin-top:0.5rem">
        Guardar y siguiente
      </button>
      <button type="button" id="btnSkip" class="crm-btn crm-btn-ghost crm-btn--block" style="margin-top:0.5rem">
        Saltar (sin marcar)
      </button>
    </div>
  </div>
</div>

<script src="assets/prospect-filters.js?v=20260620"></script>
<script src="assets/bitacora.js?v=20260620"></script>
<script>
const ESTADOS = <?= json_encode(PROSPECTO_ESTADOS, JSON_UNESCAPED_UNICODE) ?>;
const QUICK = ["no_contesta", "buzon", "no_interesa", "pensando", "interesado", "reunion_agendada", "convertido"];

let current = null;
let links = null;
let currentHistorial = [];
let selectedResult = "no_contesta";
let excludeId = 0;
let activeFilters = crmProspectFilters.initForm();

function updateFilterSummary() {
  const el = document.getElementById("filterSummary");
  if (el) el.textContent = crmProspectFilters.activeSummary(activeFilters, ESTADOS);
}

function filterQuery(extra) {
  const p = crmProspectFilters.toSearchParams(activeFilters);
  if (extra) {
    Object.entries(extra).forEach(([k, v]) => {
      if (v !== "" && v != null) p.set(k, String(v));
    });
  }
  const q = p.toString();
  return q ? `&${q}` : "";
}

const quickEl = document.getElementById("quickResults");
quickEl.innerHTML = QUICK.map(k =>
  `<button type="button" class="crm-dialer-result-btn" data-r="${k}">${ESTADOS[k]}</button>`
).join("");

document.querySelectorAll(".crm-dialer-result-btn").forEach(btn => {
  btn.addEventListener("click", () => {
    selectedResult = btn.dataset.r;
    document.querySelectorAll(".crm-dialer-result-btn").forEach(b => b.classList.remove("is-active"));
    btn.classList.add("is-active");
    document.getElementById("extraFields").classList.remove("crm-hidden");
    toggleExtras();
  });
});
document.querySelector(".crm-dialer-result-btn[data-r='no_contesta']").classList.add("is-active");

function toggleExtras() {
  document.getElementById("wrapCallback").classList.toggle("crm-hidden", selectedResult !== "pensando");
  document.getElementById("wrapReunion").classList.toggle("crm-hidden", selectedResult !== "reunion_agendada");
}

function renderWebContext(p) {
  const box = document.getElementById("dWebContext");
  if (!box) return;
  if (p.origen !== "web_contacto" && p.origen !== "web_cita") {
    box.classList.add("crm-hidden");
    box.innerHTML = "";
    return;
  }

  const rows = [];
  if (p.email) rows.push(`<div><span>Email</span><p>${escHtml(p.email)}</p></div>`);
  if (p.servicio_interes) rows.push(`<div><span>Servicio</span><p>${escHtml(p.servicio_interes)}</p></div>`);
  if (p.mensaje_web) rows.push(`<div><span>Mensaje / detalle</span><p>${escHtml(p.mensaje_web).replace(/\n/g, "<br>")}</p></div>`);

  if (!rows.length) {
    box.classList.add("crm-hidden");
    return;
  }

  box.innerHTML = `<strong class="crm-organic-context-box__title">Lo que llenó en el formulario</strong><div class="crm-organic-context-box__list">${rows.join("")}</div>`;
  box.classList.remove("crm-hidden");
}

function escHtml(s) {
  const d = document.createElement("div");
  d.textContent = s ?? "";
  return d.innerHTML;
}

async function loadNext() {
  document.getElementById("dialerLoading").classList.remove("crm-hidden");
  document.getElementById("dialerEmpty").classList.add("crm-hidden");
  document.getElementById("dialerCard").classList.add("crm-hidden");

  activeFilters = crmProspectFilters.readForm();
  crmProspectFilters.save(activeFilters);
  updateFilterSummary();

  const exclude = excludeId ? `&exclude_id=${excludeId}` : "";
  const res = await fetch(`api/prospects.php?mode=next${exclude}${filterQuery()}`);
  const data = await res.json();

  document.getElementById("dialerLoading").classList.add("crm-hidden");

  if (!data.ok || !data.prospecto) {
    document.getElementById("dialerEmpty").classList.remove("crm-hidden");
    current = null;
    return;
  }

  current = data.prospecto;
  links = data.links;
  currentHistorial = data.historial || [];
  excludeId = 0;
  renderCard();
  document.getElementById("dialerCard").classList.remove("crm-hidden");

  document.getElementById("dNotas").value = "";
  document.getElementById("dCallback").value = "";
  document.getElementById("dReunion").value = "";
  document.getElementById("dLink").value = "";
  selectedResult = "no_contesta";
  document.querySelectorAll(".crm-dialer-result-btn").forEach(b => b.classList.remove("is-active"));
  document.querySelector(".crm-dialer-result-btn[data-r='no_contesta']").classList.add("is-active");
  document.getElementById("extraFields").classList.add("crm-hidden");
  toggleExtras();
}

function renderCard() {
  const p = current;
  document.getElementById("dCat").textContent = p.categoria_nombre;
  const organic = document.getElementById("dOrganic");
  if (p.origen === "web_contacto") {
    organic.textContent = "Orgánico · Web";
    organic.classList.remove("crm-hidden");
  } else if (p.origen === "web_cita") {
    organic.textContent = "Orgánico · Cita";
    organic.classList.remove("crm-hidden");
  } else {
    organic.classList.add("crm-hidden");
  }
  const est = document.getElementById("dEstado");
  est.textContent = ESTADOS[p.estado] || p.estado;
  est.className = "status-badge status-" + p.estado;
  document.getElementById("dNombre").textContent = p.nombre;
  document.getElementById("dCiudad").textContent = p.ciudad || "Ciudad no indicada";
  const sinResp = p.intentos_sin_respuesta || 0;
  document.getElementById("dRating").textContent = p.calificacion
    ? `★ ${p.calificacion} · ${p.num_resenas || 0} reseñas · ${p.intentos || 0} intentos · sin respuesta ${sinResp}/3`
    : `${p.intentos || 0} intentos · sin respuesta ${sinResp}/3`;

  document.getElementById("dTelText").textContent = p.telefono;
  document.getElementById("dTelBtn").href = links?.tel ? "tel:" + links.tel.replace(/\s/g, "") : "#";

  const wa = document.getElementById("dWaBtn");
  wa.href = links?.whatsapp || "#";
  wa.classList.toggle("crm-hidden", !links?.whatsapp);

  const maps = document.getElementById("dMapsBtn");
  if (p.link_google_maps) {
    maps.href = p.link_google_maps;
    maps.classList.remove("crm-hidden");
  } else {
    maps.classList.add("crm-hidden");
  }

  document.getElementById("dFichaBtn").href = "prospecto.php?id=" + p.id;
  renderWebContext(p);
  renderBitacora(p, currentHistorial);
}

function renderBitacora(p, historial) {
  const wrap = document.getElementById("dBitacoraWrap");
  const el = document.getElementById("dBitacora");
  if (!wrap || !el) return;

  if (!crmBitacora.hasContent(p.notas, historial)) {
    wrap.classList.add("crm-hidden");
    el.innerHTML = "";
    return;
  }

  el.innerHTML = crmBitacora.html(p.notas, historial, ESTADOS, { compact: true, maxItems: 8 });
  wrap.classList.remove("crm-hidden");
}

document.getElementById("btnSaveNext").addEventListener("click", async () => {
  if (!current) return;

  if (selectedResult === "pensando" && !document.getElementById("dCallback").value) {
    alert("Indica la fecha de callback");
    return;
  }
  if (selectedResult === "reunion_agendada" && !document.getElementById("dReunion").value) {
    alert("Indica la fecha de reunión");
    return;
  }

  const btn = document.getElementById("btnSaveNext");
  btn.disabled = true;
  btn.textContent = "Guardando…";

  const res = await fetch("api/prospects.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      prospecto_id: current.id,
      resultado: selectedResult,
      notas: document.getElementById("dNotas").value.trim(),
      fecha_callback: document.getElementById("dCallback").value,
      fecha_reunion: document.getElementById("dReunion").value,
      link_reunion: document.getElementById("dLink").value.trim(),
      fetch_next: true,
      filters: activeFilters,
    }),
  });
  const data = await res.json();
  btn.disabled = false;
  btn.textContent = "Guardar y siguiente";

  if (!data.ok) {
    alert(data.error || "Error");
    return;
  }

  crmToast(data.oculto ? "Guardado · oculto tras 3 sin respuesta" : "Guardado ✓");

  if (data.next) {
    current = data.next;
    links = data.links;
    renderCard();
    document.getElementById("dNotas").value = "";
    document.getElementById("dCallback").value = "";
    document.getElementById("dReunion").value = "";
    document.getElementById("dLink").value = "";
    selectedResult = "no_contesta";
    document.querySelectorAll(".crm-dialer-result-btn").forEach(b => b.classList.remove("is-active"));
    document.querySelector(".crm-dialer-result-btn[data-r='no_contesta']").classList.add("is-active");
    document.getElementById("extraFields").classList.add("crm-hidden");
    toggleExtras();
    window.scrollTo({ top: 0, behavior: "smooth" });
  } else {
    document.getElementById("dialerCard").classList.add("crm-hidden");
    document.getElementById("dialerEmpty").classList.remove("crm-hidden");
    current = null;
  }
});

document.getElementById("btnSkip").addEventListener("click", () => {
  if (!current) return;
  excludeId = current.id;
  loadNext();
});

document.getElementById("btnFiltrar").addEventListener("click", () => {
  excludeId = 0;
  const details = document.getElementById("filterDetails");
  if (details && window.innerWidth < 900) details.open = false;
  loadNext();
});

updateFilterSummary();
loadNext();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . "/includes/layout.php";
