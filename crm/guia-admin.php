<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
$user = requireRole("admin");

$title = "Administrar guía comercial";
$activeNav = "guia-admin.php";
$pageSubtitle = "Sube y organiza los PDFs de speech, manual y materiales de venta";

ob_start();
?>
<div class="crm-grid-2 crm-mb-4" style="align-items:start">
  <div class="crm-card crm-p-5">
    <h2 class="crm-card__title" id="formTitle">Nuevo documento</h2>
    <form id="guiaForm" class="crm-form-stack">
      <input type="hidden" name="id" id="guiaId" value="0" />
      <div class="crm-field">
        <label class="crm-label" for="nombre">Nombre</label>
        <input class="crm-input" id="nombre" name="nombre" required placeholder="Manual Ventas Mylder" />
      </div>
      <div class="crm-field">
        <label class="crm-label" for="descripcion">Descripción corta</label>
        <input class="crm-input" id="descripcion" name="descripcion" />
      </div>
      <div class="crm-field">
        <label class="crm-label" for="orden">Orden</label>
        <input class="crm-input" id="orden" name="orden" type="number" value="0" />
      </div>
      <label class="crm-check"><input type="checkbox" id="activo" name="activo" checked /> Visible para agentes</label>
      <button type="submit" class="crm-btn crm-btn-accent">Guardar documento</button>
      <button type="button" id="btnReset" class="crm-btn crm-btn-ghost">Limpiar formulario</button>
    </form>
  </div>

  <div class="crm-card crm-p-5">
    <h2 class="crm-card__title">Subir PDF</h2>
    <p class="crm-field-hint crm-mb-4">Selecciona un documento de la lista, luego sube su PDF.</p>
    <form id="pdfForm" class="crm-form-stack">
      <input type="hidden" name="id" id="pdfGuiaId" value="0" />
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
      <tr><th>Documento</th><th>PDF</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody id="adminBody"><tr><td colspan="4" class="crm-empty">Cargando…</td></tr></tbody>
  </table>
</div>

<script>
let selectedId = 0;

async function loadAdmin() {
  const res = await fetch("api/guia-docs.php?all=1");
  const data = await res.json();
  const tbody = document.getElementById("adminBody");
  if (!data.ok) {
    tbody.innerHTML = "<tr><td colspan='4'>Error — ejecuta php scripts/run-guia-migration.php</td></tr>";
    return;
  }

  tbody.innerHTML = data.data.map(d => `
    <tr class="${selectedId === d.id ? 'is-selected' : ''}">
      <td><strong>${esc(d.nombre)}</strong>${d.descripcion ? `<div class="crm-cell-meta">${esc(d.descripcion)}</div>` : ""}</td>
      <td>${d.tiene_pdf ? "✓ PDF" : "—"}</td>
      <td>${d.activo == 1 ? "Visible" : "Oculto"}</td>
      <td class="space-x-1">
        <button type="button" class="crm-btn crm-btn-ghost crm-btn--sm edit-btn" data-id="${d.id}">Editar</button>
        <button type="button" class="crm-btn crm-btn-ghost crm-btn--sm select-pdf-btn" data-id="${d.id}">PDF</button>
        <button type="button" class="crm-btn crm-btn-ghost crm-btn--sm toggle-btn" data-id="${d.id}" data-activo="${d.activo == 1 ? 0 : 1}">${d.activo == 1 ? "Ocultar" : "Mostrar"}</button>
      </td>
    </tr>
  `).join("");

  document.querySelectorAll(".edit-btn").forEach(btn => {
    btn.addEventListener("click", () => editDoc(Number(btn.dataset.id)));
  });
  document.querySelectorAll(".select-pdf-btn").forEach(btn => {
    btn.addEventListener("click", () => selectForPdf(Number(btn.dataset.id), data.data));
  });
  document.querySelectorAll(".toggle-btn").forEach(btn => {
    btn.addEventListener("click", async () => {
      await fetch("api/guia-docs.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "toggle", id: Number(btn.dataset.id), activo: Number(btn.dataset.activo) }),
      });
      loadAdmin();
    });
  });
}

async function editDoc(id) {
  const res = await fetch("api/guia-docs.php?id=" + id);
  const json = await res.json();
  if (!json.ok) return;
  const d = json.data;
  selectedId = id;
  document.getElementById("formTitle").textContent = "Editar documento";
  document.getElementById("guiaId").value = d.id;
  document.getElementById("nombre").value = d.nombre;
  document.getElementById("descripcion").value = d.descripcion || "";
  document.getElementById("orden").value = d.orden || 0;
  document.getElementById("activo").checked = d.activo == 1;
  selectForPdf(id, [{ id: d.id, nombre: d.nombre, tiene_pdf: d.tiene_pdf }]);
  loadAdmin();
}

function selectForPdf(id, rows) {
  selectedId = id;
  document.getElementById("pdfGuiaId").value = id;
  document.getElementById("btnUploadPdf").disabled = false;
  const d = rows.find(r => r.id == id);
  document.getElementById("pdfStatus").textContent = d
    ? `PDF para: ${d.nombre}${d.tiene_pdf ? " (reemplazará el actual)" : ""}`
    : "";
}

document.getElementById("guiaForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const res = await fetch("api/guia-docs.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      action: "save",
      id: Number(fd.get("id")),
      nombre: fd.get("nombre"),
      descripcion: fd.get("descripcion"),
      orden: Number(fd.get("orden")),
      activo: document.getElementById("activo").checked ? 1 : 0,
    }),
  });
  const data = await res.json();
  if (data.ok) {
    crmToast("Documento guardado");
    if (!Number(fd.get("id"))) {
      document.getElementById("guiaId").value = data.id;
      selectForPdf(data.id, [{ id: data.id, nombre: fd.get("nombre"), tiene_pdf: false }]);
    }
    loadAdmin();
  } else {
    alert(data.error);
  }
});

document.getElementById("pdfForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const id = Number(document.getElementById("pdfGuiaId").value);
  if (!id) { alert("Selecciona un documento primero"); return; }
  const fd = new FormData();
  fd.append("id", String(id));
  fd.append("pdf", document.getElementById("pdfFile").files[0]);
  const res = await fetch("api/guia-docs.php", { method: "POST", body: fd });
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
  document.getElementById("guiaForm").reset();
  document.getElementById("guiaId").value = "0";
  document.getElementById("formTitle").textContent = "Nuevo documento";
  selectedId = 0;
  document.getElementById("pdfGuiaId").value = "0";
  document.getElementById("btnUploadPdf").disabled = true;
  document.getElementById("pdfStatus").textContent = "";
});

function esc(s) { const d = document.createElement("div"); d.textContent = s ?? ""; return d.innerHTML; }
loadAdmin();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . "/includes/layout.php";
