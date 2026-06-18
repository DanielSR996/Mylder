<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
$user = requireRole("admin");

$title = "Administrar cotizaciones";
$activeNav = "cotizaciones-admin.php";
$pageSubtitle = "Edita servicios, precios y sube un PDF por cotización";

ob_start();
?>
<div class="crm-grid-2 crm-mb-4" style="align-items:start">
  <div class="crm-card crm-p-5">
    <h2 class="crm-card__title" id="formTitle">Nueva cotización</h2>
    <form id="cotizForm" class="crm-form-stack">
      <input type="hidden" name="id" id="cotizId" value="0" />
      <div class="crm-field">
        <label class="crm-label" for="nombre">Nombre del servicio</label>
        <input class="crm-input" id="nombre" name="nombre" required />
      </div>
      <div class="crm-field">
        <label class="crm-label" for="categoria">Categoría</label>
        <select class="crm-select" id="categoria" name="categoria">
          <option value="sitio">Sitio web</option>
          <option value="ecommerce">E-commerce</option>
          <option value="branding">Branding</option>
          <option value="automatizacion">Automatización IA</option>
          <option value="soporte">Soporte web</option>
          <option value="general">General</option>
        </select>
      </div>
      <div class="crm-field">
        <label class="crm-label" for="resumen">Resumen corto</label>
        <input class="crm-input" id="resumen" name="resumen" />
      </div>
      <div class="crm-field">
        <label class="crm-label" for="precio_etiqueta">Precio estándar</label>
        <input class="crm-input" id="precio_etiqueta" name="precio_etiqueta" placeholder="$9,500 MXN" />
      </div>
      <div class="crm-field">
        <label class="crm-label" for="precio_minimo">Precio mínimo (opcional)</label>
        <input class="crm-input" id="precio_minimo" name="precio_minimo" />
      </div>
      <div class="crm-field">
        <label class="crm-label" for="descripcion">Descripción</label>
        <textarea class="crm-textarea" id="descripcion" name="descripcion" rows="3"></textarea>
      </div>
      <div class="crm-field">
        <label class="crm-label" for="incluye">Qué incluye (una línea por ítem)</label>
        <textarea class="crm-textarea" id="incluye" name="incluye" rows="4" placeholder="Diseño a código&#10;SEO básico&#10;Dominio primer año"></textarea>
      </div>
      <div class="crm-field">
        <label class="crm-label" for="precio_nota">Tip para el agente (venta)</label>
        <textarea class="crm-textarea" id="precio_nota" name="precio_nota" rows="2"></textarea>
      </div>
      <div class="crm-field">
        <label class="crm-label" for="comision_nota">Nota de comisión</label>
        <input class="crm-input" id="comision_nota" name="comision_nota" />
      </div>
      <div class="crm-field">
        <label class="crm-label" for="orden">Orden</label>
        <input class="crm-input" id="orden" name="orden" type="number" value="0" />
      </div>
      <label class="crm-check"><input type="checkbox" id="activo" name="activo" checked /> Activa (visible para agentes)</label>
      <button type="submit" class="crm-btn crm-btn-accent">Guardar cotización</button>
      <button type="button" id="btnReset" class="crm-btn crm-btn-ghost">Limpiar formulario</button>
    </form>
  </div>

  <div class="crm-card crm-p-5">
    <h2 class="crm-card__title">Subir PDF</h2>
    <p class="crm-field-hint crm-mb-4">Selecciona una cotización de la lista, luego sube su PDF.</p>
    <form id="pdfForm" class="crm-form-stack">
      <input type="hidden" name="id" id="pdfCotizId" value="0" />
      <div class="crm-field">
        <label class="crm-label" for="pdfFile">Archivo PDF</label>
        <input type="file" id="pdfFile" name="pdf" accept=".pdf,application/pdf" required class="crm-file" />
      </div>
      <button type="submit" class="crm-btn crm-btn-primary" id="btnUploadPdf" disabled>Subir PDF</button>
    </form>
    <p id="pdfStatus" class="crm-field-hint" style="margin-top:0.75rem"></p>
  </div>
</div>

<div class="crm-card crm-table-wrap">
  <table class="crm-table">
    <thead>
      <tr><th>Servicio</th><th>Precio</th><th>PDF</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody id="adminBody"><tr><td colspan="5" class="crm-empty">Cargando…</td></tr></tbody>
  </table>
</div>

<script>
let selectedId = 0;

async function loadAdmin() {
  const res = await fetch("api/cotizaciones.php?all=1");
  const data = await res.json();
  const tbody = document.getElementById("adminBody");
  if (!data.ok) {
    tbody.innerHTML = "<tr><td colspan='5'>Error</td></tr>";
    return;
  }

  tbody.innerHTML = data.data.map(c => `
    <tr class="${selectedId === c.id ? 'is-selected' : ''}">
      <td><strong>${esc(c.nombre)}</strong><div class="crm-cell-meta">${esc(c.categoria_label)}</div></td>
      <td>${esc(c.precio_etiqueta || "—")}</td>
      <td>${c.tiene_pdf ? "✓ PDF" : "—"}</td>
      <td>${c.activo == 1 ? "Activa" : "Oculta"}</td>
      <td class="space-x-1">
        <button type="button" class="crm-btn crm-btn-ghost crm-btn--sm edit-btn" data-id="${c.id}">Editar</button>
        <button type="button" class="crm-btn crm-btn-ghost crm-btn--sm select-pdf-btn" data-id="${c.id}">PDF</button>
        <button type="button" class="crm-btn crm-btn-ghost crm-btn--sm toggle-btn" data-id="${c.id}" data-activo="${c.activo == 1 ? 0 : 1}">${c.activo == 1 ? "Ocultar" : "Activar"}</button>
      </td>
    </tr>
  `).join("");

  document.querySelectorAll(".edit-btn").forEach(btn => {
    btn.addEventListener("click", () => editCotiz(Number(btn.dataset.id)));
  });
  document.querySelectorAll(".select-pdf-btn").forEach(btn => {
    btn.addEventListener("click", () => selectForPdf(Number(btn.dataset.id), data.data));
  });
  document.querySelectorAll(".toggle-btn").forEach(btn => {
    btn.addEventListener("click", async () => {
      await fetch("api/cotizaciones.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "toggle", id: Number(btn.dataset.id), activo: Number(btn.dataset.activo) }),
      });
      loadAdmin();
    });
  });
}

async function editCotiz(id) {
  const res = await fetch("api/cotizaciones.php?id=" + id);
  const json = await res.json();
  if (!json.ok) return;
  const c = json.data;
  selectedId = id;
  document.getElementById("formTitle").textContent = "Editar cotización";
  document.getElementById("cotizId").value = c.id;
  document.getElementById("nombre").value = c.nombre;
  document.getElementById("categoria").value = c.categoria;
  document.getElementById("resumen").value = c.resumen || "";
  document.getElementById("precio_etiqueta").value = c.precio_etiqueta || "";
  document.getElementById("precio_minimo").value = c.precio_minimo || "";
  document.getElementById("descripcion").value = c.descripcion || "";
  document.getElementById("incluye").value = (c.incluye_lista || []).join("\n");
  document.getElementById("precio_nota").value = c.precio_nota || "";
  document.getElementById("comision_nota").value = c.comision_nota || "";
  document.getElementById("orden").value = c.orden || 0;
  document.getElementById("activo").checked = c.activo == 1;
  selectForPdf(id, [{ id: c.id, nombre: c.nombre, tiene_pdf: c.tiene_pdf }]);
  loadAdmin();
}

function selectForPdf(id, rows) {
  selectedId = id;
  document.getElementById("pdfCotizId").value = id;
  document.getElementById("btnUploadPdf").disabled = false;
  const c = rows.find(r => r.id == id);
  document.getElementById("pdfStatus").textContent = c
    ? `PDF para: ${c.nombre}${c.tiene_pdf ? " (reemplazará el actual)" : ""}`
    : "";
}

document.getElementById("cotizForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const incluyeLines = String(fd.get("incluye") || "").split("\n").map(s => s.trim()).filter(Boolean);
  const res = await fetch("api/cotizaciones.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      action: "save",
      id: Number(fd.get("id")),
      nombre: fd.get("nombre"),
      categoria: fd.get("categoria"),
      resumen: fd.get("resumen"),
      precio_etiqueta: fd.get("precio_etiqueta"),
      precio_minimo: fd.get("precio_minimo"),
      descripcion: fd.get("descripcion"),
      incluye: incluyeLines,
      precio_nota: fd.get("precio_nota"),
      comision_nota: fd.get("comision_nota"),
      orden: Number(fd.get("orden")),
      activo: document.getElementById("activo").checked ? 1 : 0,
    }),
  });
  const data = await res.json();
  if (data.ok) {
    crmToast("Cotización guardada");
    if (!Number(fd.get("id"))) {
      document.getElementById("cotizId").value = data.id;
      selectForPdf(data.id, [{ id: data.id, nombre: fd.get("nombre"), tiene_pdf: false }]);
    }
    loadAdmin();
  } else {
    alert(data.error);
  }
});

document.getElementById("pdfForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const id = Number(document.getElementById("pdfCotizId").value);
  if (!id) { alert("Selecciona una cotización primero"); return; }
  const fd = new FormData();
  fd.append("id", String(id));
  fd.append("pdf", document.getElementById("pdfFile").files[0]);
  const res = await fetch("api/cotizaciones.php", { method: "POST", body: fd });
  const data = await res.json();
  if (data.ok) {
    crmToast("PDF subido");
    document.getElementById("pdfFile").value = "";
    loadAdmin();
  } else {
    alert(data.error);
  }
});

document.getElementById("btnReset").addEventListener("click", () => {
  document.getElementById("cotizForm").reset();
  document.getElementById("cotizId").value = "0";
  document.getElementById("formTitle").textContent = "Nueva cotización";
  selectedId = 0;
  document.getElementById("pdfCotizId").value = "0";
  document.getElementById("btnUploadPdf").disabled = true;
  document.getElementById("pdfStatus").textContent = "";
});

function esc(s) { const d = document.createElement("div"); d.textContent = s ?? ""; return d.innerHTML; }
loadAdmin();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . "/includes/layout.php";
