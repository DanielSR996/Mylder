<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
$user = requireLogin();

$title = "Dashboard";
$activeNav = "dashboard.php";
$pageSubtitle = "Hola, <strong>" . authEsc($user["nombre"]) . "</strong> · aquí tienes el resumen de hoy";

ob_start();
?>
<div id="statsGrid" class="crm-grid-stats">
  <?php for ($i = 0; $i < 4; $i++): ?>
  <div class="crm-card crm-stat crm-card--interactive">
    <div class="crm-skeleton" style="width:2.75rem;height:2.75rem;border-radius:14px;margin-bottom:0.85rem"></div>
    <div class="crm-skeleton" style="width:55%;height:0.75rem"></div>
    <div class="crm-skeleton crm-skeleton--lg"></div>
  </div>
  <?php endfor; ?>
</div>

<div id="organicBanner" class="crm-card crm-p-4 crm-mb-4 crm-hidden" style="border:1.5px solid #6ee7b7;background:#ecfdf5">
  <strong style="color:#065f46">Leads orgánicos del sitio web:</strong>
  <span id="organicCount">0</span> activos con prioridad alta.
  <a href="queue.php?solo_organicos=1" class="crm-link" style="margin-left:0.5rem">Ver orgánicos →</a>
</div>

<div class="crm-grid-2 crm-mb-4" id="panelHoy">
  <div class="crm-card crm-panel crm-p-5">
    <h2 class="crm-card__title">Callbacks hoy</h2>
    <div id="listaCallbacks"><div class="crm-skeleton" style="height:2rem"></div></div>
  </div>
  <div class="crm-card crm-panel crm-p-5">
    <h2 class="crm-card__title">Reuniones próximas</h2>
    <div id="listaReuniones"><div class="crm-skeleton" style="height:2rem"></div></div>
  </div>
</div>

<div class="crm-grid-2">
  <div class="crm-card crm-panel crm-p-5">
    <h2 class="crm-card__title">Por categoría</h2>
    <div id="statsCategorias"><div class="crm-skeleton" style="height:2.2rem;margin-bottom:0.5rem"></div><div class="crm-skeleton" style="height:2.2rem"></div></div>
  </div>
  <div class="crm-card crm-panel crm-p-5">
    <h2 class="crm-card__title">Por estado</h2>
    <div id="statsEstados"><div class="crm-skeleton" style="height:2.2rem;margin-bottom:0.5rem"></div><div class="crm-skeleton" style="height:2.2rem"></div></div>
  </div>
</div>

<?php if ($user["rol"] === "admin"): ?>
<div class="crm-card crm-panel crm-p-5 crm-mt-4">
  <h2 class="crm-card__title">Últimas importaciones</h2>
  <div id="statsImportaciones"><div class="crm-skeleton" style="height:2.2rem"></div></div>
</div>
<?php endif; ?>

<script>
const ESTADOS = <?= json_encode(PROSPECTO_ESTADOS, JSON_UNESCAPED_UNICODE) ?>;

const STAT_ICONS = {
  pendientes: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>`,
  interesados: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`,
  callbacks: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
  contactos: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>`,
};

const STAT_CONFIG = [
  { key: "pendientes", label: "Pendientes", iconClass: "crm-stat__icon--pending", valueClass: "" },
  { key: "interesados", label: "Interesados", iconClass: "crm-stat__icon--success", valueClass: "crm-stat__value--green" },
  { key: "callbacks", label: "Callbacks vencidos", iconClass: "crm-stat__icon--warn", valueClass: "crm-stat__value--amber" },
  { key: "contactos", label: "Contactos hoy", iconClass: "crm-stat__icon--info", valueClass: "" },
];

async function loadStats() {
  try {
    const res = await fetch("api/stats.php");
    const raw = await res.text();
    let data;
    try {
      data = JSON.parse(raw);
    } catch (parseErr) {
      showDashboardError("No se pudo leer el resumen (respuesta inválida). Recarga la página.");
      return;
    }
    if (!data.ok) {
      showDashboardError(data.error || "Error al cargar estadísticas.");
      return;
    }

  const s = data.stats;
  const values = {
    pendientes: (s.por_estado || []).find(e => e.estado === "pendiente")?.total || 0,
    interesados: (s.por_estado || []).find(e => e.estado === "interesado")?.total || 0,
    callbacks: s.callbacks_vencidos || 0,
    contactos: s.contactos_hoy || 0,
  };

  document.getElementById("statsGrid").innerHTML = STAT_CONFIG.map((cfg, i) => `
    <div class="crm-card crm-stat crm-card--interactive" style="animation-delay:${0.04 + i * 0.06}s">
      <div class="crm-stat__icon ${cfg.iconClass}">${STAT_ICONS[cfg.key]}</div>
      <div class="crm-stat__label">${cfg.label}</div>
      <div class="crm-stat__value ${cfg.valueClass}" data-count="${values[cfg.key]}">0</div>
    </div>
  `).join("");

  document.querySelectorAll("[data-count]").forEach(el => {
    crmAnimateCount(el, parseInt(el.dataset.count, 10) || 0);
  });

  const organicN = s.organicos_activos || 0;
  const banner = document.getElementById("organicBanner");
  if (banner && organicN > 0) {
    banner.classList.remove("crm-hidden");
    document.getElementById("organicCount").textContent = organicN;
  } else if (banner) {
    banner.classList.add("crm-hidden");
  }

  document.getElementById("listaCallbacks").innerHTML = (s.callbacks_hoy_lista || []).map(p =>
    `<a href="prospecto.php?id=${p.id}" class="crm-hoy-row">
      <div><div class="crm-list-row__name">${esc(p.nombre)}</div><div class="crm-list-row__meta">${esc(p.categoria)} · ${esc(p.fecha_callback)}</div></div>
      <span class="crm-hoy-row__action">Ver →</span>
    </a>`
  ).join("") || '<p class="crm-empty">Sin callbacks pendientes hoy.</p>';

  document.getElementById("listaReuniones").innerHTML = (s.reuniones_hoy_lista || []).map(p =>
    `<div class="crm-hoy-row">
      <div><div class="crm-list-row__name">${esc(p.nombre)}</div><div class="crm-list-row__meta">${esc(p.categoria)} · ${esc(p.fecha_reunion)}</div></div>
      ${p.link_reunion ? `<a href="${esc(p.link_reunion)}" target="_blank" rel="noopener" class="crm-hoy-row__action">Meet →</a>` : `<a href="prospecto.php?id=${p.id}" class="crm-hoy-row__action">Ver →</a>`}
    </div>`
  ).join("") || '<p class="crm-empty">Sin reuniones en los próximos 2 días.</p>';

  document.getElementById("statsCategorias").innerHTML = (s.por_categoria || []).map(c =>
    `<div class="crm-list-row">
      <div><div class="crm-list-row__name">${esc(c.nombre)}</div></div>
      <span class="crm-list-row__badge">${c.total} · ${c.pendientes} pend.</span>
    </div>`
  ).join("") || '<p class="crm-empty">Sin datos aún — importa un CSV para empezar.</p>';

  document.getElementById("statsEstados").innerHTML = (s.por_estado || []).map(e =>
    `<div class="crm-list-row">
      <div><div class="crm-list-row__name">${ESTADOS[e.estado] || e.estado}</div></div>
      <span class="crm-list-row__badge">${e.total}</span>
    </div>`
  ).join("") || '<p class="crm-empty">Sin actividad registrada.</p>';

  const impEl = document.getElementById("statsImportaciones");
  if (impEl && s.ultimas_importaciones) {
    impEl.innerHTML = s.ultimas_importaciones.map(i =>
      `<div class="crm-list-row">
        <div>
          <div class="crm-list-row__name">${esc(i.nombre_archivo)}</div>
          <div class="crm-list-row__meta">${esc(i.categoria)}</div>
        </div>
        <span class="crm-list-row__badge">+${i.filas_nuevas} nuevos</span>
      </div>`
    ).join("") || '<p class="crm-empty">Sin importaciones todavía.</p>';
  }
  } catch (err) {
    showDashboardError("Error de conexión al cargar el dashboard.");
  }
}

function showDashboardError(msg) {
  document.getElementById("statsGrid").innerHTML = `<div class="crm-alert crm-alert--err" style="grid-column:1/-1">${esc(msg)}</div>`;
  ["listaCallbacks","listaReuniones","statsCategorias","statsEstados"].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.innerHTML = `<p class="crm-empty">${esc(msg)}</p>`;
  });
}

function esc(s) {
  const d = document.createElement("div");
  d.textContent = s ?? "";
  return d.innerHTML;
}

loadStats();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . "/includes/layout.php";
