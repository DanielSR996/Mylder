<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
$user = requireRole("admin");

$title = "Categorías";
$activeNav = "categorias.php";
$pageSubtitle = "Organiza tus prospectos por vertical de negocio";

ob_start();
?>
<div class="crm-card crm-p-6 crm-max-w-xl crm-mb-4">
  <h2 class="crm-card__title">Nueva categoría</h2>
  <form id="catForm" class="crm-form-row">
    <input name="nombre" placeholder="Nombre (ej. Spas)" required class="crm-input" />
    <input name="descripcion" placeholder="Descripción opcional" class="crm-input" />
    <button type="submit" class="crm-btn crm-btn-accent">Crear</button>
  </form>
</div>

<div class="crm-card crm-table-wrap">
  <table class="crm-table">
    <thead>
      <tr><th>Nombre</th><th>Slug</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody id="catsBody"><tr><td colspan="4" class="text-center py-6">Cargando…</td></tr></tbody>
  </table>
</div>

<script>
async function loadCats() {
  const res = await fetch("api/categories.php");
  const data = await res.json();
  const tbody = document.getElementById("catsBody");
  if (!data.ok) return;

  tbody.innerHTML = data.data.map(c => `
    <tr>
      <td>${esc(c.nombre)}</td>
      <td class="text-slate-500">${esc(c.slug)}</td>
      <td>${c.activo == 1 ? '<span class="text-green-600">Activa</span>' : '<span class="text-red-600">Inactiva</span>'}</td>
      <td>
        <button class="text-xs crm-btn crm-btn-ghost toggle-btn" data-id="${c.id}" data-activo="${c.activo == 1 ? 0 : 1}">
          ${c.activo == 1 ? 'Desactivar' : 'Activar'}
        </button>
      </td>
    </tr>
  `).join("");

  document.querySelectorAll(".toggle-btn").forEach(btn => {
    btn.addEventListener("click", async () => {
      await fetch("api/categories.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "toggle", id: Number(btn.dataset.id), activo: Number(btn.dataset.activo) }),
      });
      loadCats();
    });
  });
}

document.getElementById("catForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const res = await fetch("api/categories.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ action: "create", nombre: fd.get("nombre"), descripcion: fd.get("descripcion") }),
  });
  const data = await res.json();
  if (data.ok) { e.target.reset(); crmToast("Categoría creada"); loadCats(); }
  else alert(data.error);
});

function esc(s) { const d = document.createElement("div"); d.textContent = s; return d.innerHTML; }
loadCats();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . "/includes/layout.php";
