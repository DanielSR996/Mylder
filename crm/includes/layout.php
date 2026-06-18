<?php
declare(strict_types=1);

/** @var array{id:int,nombre:string,email:string,rol:string} $user */
/** @var string $title */
/** @var string $activeNav */
/** @var string $content */

if (!isset($user, $title, $content)) {
  throw new RuntimeException("layout.php requiere \$user, \$title y \$content");
}

require_once __DIR__ . "/brand.php";

$navItems = [
  "dashboard.php" => "Dashboard",
  "llamar.php" => "Modo llamada",
  "queue.php" => "Prospectos",
  "guia.php" => "Guía comercial",
  "cotizaciones.php" => "Cotizaciones",
];

if ($user["rol"] === "admin") {
  $navItems["guia-admin.php"] = "Admin guía";
  $navItems["cotizaciones-admin.php"] = "Admin cotiz.";
  $navItems["reportes.php"] = "Reportes";
  $navItems["import.php"] = "Importar CSV";
  $navItems["categorias.php"] = "Categorías";
  $navItems["usuarios.php"] = "Usuarios";
}

$pageSubtitle = $pageSubtitle ?? "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title><?= authEsc($title) ?> · Mylder</title>
  <?= crmBrandHeadLinks() ?>
  <link rel="stylesheet" href="assets/crm.css?v=20260621" />
</head>
<body class="crm-app">
  <header class="crm-header">
    <div class="crm-header__bar">
      <a href="dashboard.php" class="crm-brand" aria-label="Mylder Solutions inicio">
        <span class="crm-brand__name">Mylder</span>
        <span class="crm-brand__suffix">Solutions</span>
      </a>
      <div class="crm-header__actions">
        <?php if ($user["rol"] === "admin"): ?>
        <div class="crm-presence" id="crmPresence">
          <button type="button" class="crm-presence__toggle" id="crmPresenceToggle" aria-expanded="false" aria-controls="crmPresencePanel">
            <span class="crm-presence__dot" aria-hidden="true"></span>
            <span class="crm-presence__label">En línea</span>
            <span class="crm-presence__count" id="crmPresenceCount">—</span>
          </button>
          <div class="crm-presence__panel" id="crmPresencePanel" hidden>
            <div class="crm-presence__panel-head">
              <span>Equipo conectado</span>
              <button type="button" class="crm-presence__close" id="crmPresenceClose" aria-label="Cerrar">×</button>
            </div>
            <ul class="crm-presence__list" id="crmPresenceList">
              <li class="crm-presence__empty">Cargando…</li>
            </ul>
          </div>
        </div>
        <?php endif; ?>
        <span class="crm-user-pill">
          <span class="crm-user-pill__name"><?= authEsc($user["nombre"]) ?></span>
          <span class="crm-user-pill__role"> · <?= authEsc($user["rol"]) ?></span>
        </span>
        <a href="logout.php" class="crm-btn crm-btn-header-logout">Salir</a>
        <button type="button" class="crm-nav-toggle" aria-expanded="false" aria-controls="crmNav" aria-label="Abrir menú">
          <span class="crm-nav-toggle__line"></span>
          <span class="crm-nav-toggle__line"></span>
          <span class="crm-nav-toggle__line"></span>
        </button>
      </div>
    </div>
    <nav id="crmNav" class="crm-nav" aria-label="Navegación principal">
      <?php foreach ($navItems as $href => $label): ?>
        <a href="<?= authEsc($href) ?>" class="crm-nav-link <?= ($activeNav ?? "") === $href ? "active" : "" ?>"><?= authEsc($label) ?></a>
      <?php endforeach; ?>
    </nav>
  </header>

  <main class="crm-main">
    <div class="crm-page-head">
      <h1 class="crm-page-title"><?= authEsc($title) ?></h1>
      <?php if ($pageSubtitle !== ""): ?>
        <p class="crm-page-sub"><?= $pageSubtitle ?></p>
      <?php endif; ?>
    </div>
    <?= $content ?>
  </main>

  <div id="crmToast" class="toast" role="status"></div>
  <script>
    function crmToast(msg) {
      const el = document.getElementById("crmToast");
      if (!el) return;
      el.textContent = msg;
      el.classList.add("show");
      setTimeout(() => el.classList.remove("show"), 3200);
    }

    function crmAnimateCount(el, target, duration = 750) {
      const start = performance.now();
      function tick(now) {
        const p = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(target * eased);
        if (p < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    }

    /** URL de PDF embebido sin barra ni miniaturas del visor del navegador */
    function crmPdfViewerSrc(url) {
      const base = String(url || "").split("#")[0];
      return base + "#toolbar=0&navpanes=0&scrollbar=0&view=FitH";
    }

    (function initCrmNav() {
      const toggle = document.querySelector(".crm-nav-toggle");
      const nav = document.getElementById("crmNav");
      if (!toggle || !nav) return;

      function setOpen(open) {
        nav.classList.toggle("is-open", open);
        toggle.classList.toggle("is-open", open);
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        toggle.setAttribute("aria-label", open ? "Cerrar menú" : "Abrir menú");
        document.body.classList.toggle("crm-nav-open", open);
      }

      toggle.addEventListener("click", () => setOpen(!nav.classList.contains("is-open")));

      nav.querySelectorAll(".crm-nav-link").forEach((link) => {
        link.addEventListener("click", () => setOpen(false));
      });

      window.addEventListener("resize", () => {
        if (window.innerWidth >= 900) setOpen(false);
      });
    })();

    (function initCrmPresence() {
      const root = document.getElementById("crmPresence");
      if (!root) return;

      const toggle = document.getElementById("crmPresenceToggle");
      const panel = document.getElementById("crmPresencePanel");
      const closeBtn = document.getElementById("crmPresenceClose");
      const countEl = document.getElementById("crmPresenceCount");
      const listEl = document.getElementById("crmPresenceList");

      function setPanelOpen(open) {
        panel.hidden = !open;
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        root.classList.toggle("is-open", open);
        document.body.classList.toggle("crm-presence-open", open);
      }

      setPanelOpen(false);

      toggle.addEventListener("click", (e) => {
        e.stopPropagation();
        setPanelOpen(panel.hidden);
      });

      closeBtn?.addEventListener("click", (e) => {
        e.stopPropagation();
        setPanelOpen(false);
      });

      document.addEventListener("click", (e) => {
        if (!root.contains(e.target)) setPanelOpen(false);
      });

      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !panel.hidden) setPanelOpen(false);
      });

      function esc(s) {
        const d = document.createElement("div");
        d.textContent = s ?? "";
        return d.innerHTML;
      }

      async function loadPresence() {
        try {
          const res = await fetch("api/presence.php");
          const raw = await res.text();
          let data;
          try {
            data = JSON.parse(raw);
          } catch {
            countEl.textContent = "—";
            listEl.innerHTML = `<li class="crm-presence__empty">Error al cargar (${res.status})</li>`;
            return;
          }

          if (!data.ok) {
            countEl.textContent = "—";
            listEl.innerHTML = `<li class="crm-presence__empty">${esc(data.error || "No disponible")}</li>`;
            return;
          }

          if (data.migration_required) {
            countEl.textContent = "—";
            listEl.innerHTML = `<li class="crm-presence__empty">${esc(data.message || "Falta migración de presencia (sql/mylder_crm_presence.sql).")}</li>`;
            return;
          }

          countEl.textContent = String(data.online_count ?? 0);

          const rows = Array.isArray(data.data) ? data.data : [];
          if (!rows.length) {
            listEl.innerHTML = `<li class="crm-presence__empty">Sin usuarios en el equipo</li>`;
            return;
          }

          listEl.innerHTML = rows.map((u) => {
            const duration = u.online ? u.conectado_desde : u.duracion_ultima_sesion;
            const status = u.online ? "En línea ahora" : (u.ultima_vez || "Sin registro");
            return `
            <li class="crm-presence__item ${u.online ? "is-online" : ""}">
              <span class="crm-presence__status" aria-hidden="true"></span>
              <span class="crm-presence__info">
                <span class="crm-presence__name">${esc(u.nombre)}</span>
                ${duration ? `<span class="crm-presence__duration">${esc(duration)}</span>` : ""}
                <span class="crm-presence__meta">${esc(status)} · ${esc(u.rol)}</span>
              </span>
            </li>`;
          }).join("");
        } catch {
          countEl.textContent = "—";
          listEl.innerHTML = `<li class="crm-presence__empty">Error al cargar</li>`;
        }
      }

      loadPresence();
      setInterval(loadPresence, 30000);
    })();

    (function initCrmPresenceHeartbeat() {
      const ping = () => fetch("api/presence-heartbeat.php").catch(() => {});
      ping();
      setInterval(ping, 45000);
    })();
  </script>
</body>
</html>
