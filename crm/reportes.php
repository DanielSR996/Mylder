<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
$user = requireRole("admin");

$title = "Reportes";
$activeNav = "reportes.php";
$pageSubtitle = "Exporta prospectos e historial de llamadas por agente";

$agentes = db()->query("SELECT id, nombre FROM usuarios WHERE activo = 1 ORDER BY nombre")->fetchAll();

ob_start();
?>
<div class="crm-card crm-p-5 crm-mb-4">
  <h2 class="crm-card__title">Filtros</h2>
  <div class="crm-filter-bar">
    <div class="crm-field">
      <label class="crm-label" for="fDesde">Desde</label>
      <input type="date" id="fDesde" class="crm-input" />
    </div>
    <div class="crm-field">
      <label class="crm-label" for="fHasta">Hasta</label>
      <input type="date" id="fHasta" class="crm-input" />
    </div>
    <div class="crm-field">
      <label class="crm-label" for="fAgente">Agente</label>
      <select id="fAgente" class="crm-select">
        <option value="">Todos</option>
        <?php foreach ($agentes as $a): ?>
          <option value="<?= (int) $a["id"] ?>"><?= authEsc($a["nombre"]) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="crm-field">
      <label class="crm-label" for="fEstado">Estado (prospectos)</label>
      <select id="fEstado" class="crm-select">
        <option value="">Todos</option>
        <?php foreach (PROSPECTO_ESTADOS as $key => $label): ?>
          <option value="<?= authEsc($key) ?>"><?= authEsc($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="crm-field">
      <label class="crm-label" for="fOrigen">Origen</label>
      <select id="fOrigen" class="crm-select">
        <option value="">Todos</option>
        <option value="csv">Importado CSV</option>
        <option value="web_contacto">Orgánico · Contacto</option>
        <option value="web_cita">Orgánico · Cita</option>
      </select>
    </div>
    <div class="crm-field">
      <button type="button" id="btnResumen" class="crm-btn crm-btn-primary crm-btn--block">Actualizar resumen</button>
    </div>
  </div>
</div>

<div id="summaryGrid" class="crm-grid-stats crm-mb-4"></div>

<div class="crm-grid-2 crm-mb-4">
  <div class="crm-card crm-p-5">
    <h2 class="crm-card__title">Intentos por agente</h2>
    <div id="byAgent"><p class="crm-empty">Carga el resumen…</p></div>
  </div>
  <div class="crm-card crm-p-5">
    <h2 class="crm-card__title">Por resultado</h2>
    <div id="byResult"><p class="crm-empty">Carga el resumen…</p></div>
  </div>
</div>

<div class="crm-card crm-p-5">
  <h2 class="crm-card__title">Descargar CSV</h2>
  <div class="crm-form-row">
    <a href="#" id="dlProspectos" class="crm-btn crm-btn-accent">Exportar prospectos</a>
    <a href="#" id="dlIntentos" class="crm-btn crm-btn-primary">Exportar historial de llamadas</a>
  </div>
  <p class="crm-field-hint" style="margin-top:0.75rem">Los CSV usan UTF-8 para Excel. Incluyen quién atendió cada intento.</p>
</div>

<script>
function filterParams() {
  const p = new URLSearchParams();
  const desde = document.getElementById("fDesde").value;
  const hasta = document.getElementById("fHasta").value;
  const agente = document.getElementById("fAgente").value;
  const estado = document.getElementById("fEstado").value;
  const origen = document.getElementById("fOrigen").value;
  if (desde) p.set("desde", desde);
  if (hasta) p.set("hasta", hasta);
  if (agente) p.set("agente_id", agente);
  if (estado) p.set("estado", estado);
  if (origen) p.set("origen", origen);
  return p;
}

async function loadSummary() {
  const res = await fetch("api/reports.php?" + filterParams());
  const data = await res.json();
  if (!data.ok) return;

  const s = data.summary;
  document.getElementById("summaryGrid").innerHTML = `
    <div class="crm-card crm-stat crm-p-5"><div class="crm-stat__label">Prospectos</div><div class="crm-stat__value">${s.total_prospectos}</div></div>
    <div class="crm-card crm-stat crm-p-5"><div class="crm-stat__label">Orgánicos web</div><div class="crm-stat__value crm-stat__value--green">${s.organicos}</div></div>
    <div class="crm-card crm-stat crm-p-5"><div class="crm-stat__label">Ocultos (3+ sin respuesta)</div><div class="crm-stat__value crm-stat__value--amber">${s.ocultos}</div></div>
    <div class="crm-card crm-stat crm-p-5"><div class="crm-stat__label">Estados distintos</div><div class="crm-stat__value">${(s.por_estado || []).length}</div></div>
  `;

  document.getElementById("byAgent").innerHTML = (s.intentos_por_agente || []).map(r =>
    `<div class="crm-hoy-row"><div><div class="crm-list-row__name">${esc(r.agente)}</div></div><span class="crm-hoy-row__action">${r.total} intentos</span></div>`
  ).join("") || '<p class="crm-empty">Sin datos en el rango.</p>';

  document.getElementById("byResult").innerHTML = (s.intentos_por_resultado || []).map(r =>
    `<div class="crm-hoy-row"><div><div class="crm-list-row__name">${esc(r.resultado)}</div></div><span class="crm-hoy-row__action">${r.total}</span></div>`
  ).join("") || '<p class="crm-empty">Sin datos.</p>';
}

function esc(s) { const d = document.createElement("div"); d.textContent = s ?? ""; return d.innerHTML; }

document.getElementById("btnResumen").addEventListener("click", loadSummary);
document.getElementById("dlProspectos").addEventListener("click", (e) => {
  e.preventDefault();
  const p = filterParams();
  p.set("export", "csv_prospectos");
  window.location.href = "api/reports.php?" + p;
});
document.getElementById("dlIntentos").addEventListener("click", (e) => {
  e.preventDefault();
  const p = filterParams();
  p.delete("estado");
  p.delete("origen");
  p.set("export", "csv_intentos");
  window.location.href = "api/reports.php?" + p;
});

loadSummary();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . "/includes/layout.php";
