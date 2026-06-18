<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
$user = requireRole("admin");

$title = "Importar CSV";
$activeNav = "import.php";
$pageSubtitle = "Sube prospectos categorizados para tu equipo de contacto";

$catStmt = db()->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre");
$categorias = $catStmt->fetchAll();

ob_start();
?>
<div class="crm-card crm-p-6 crm-max-w-xl">
  <p class="crm-card__hint">
    Columnas: Ciudad, Nombre, Telefono, Direccion, Calificacion, Num_Resenas, Enlace_Actual, Link_Google_Maps.
    Opcional: Comentarios (se guardan en notas). Guarda el CSV en UTF-8; si Ciudad viene vacía o dice «Ciudad», intentamos inferirla desde el link de Google Maps.
  </p>

  <form id="importForm" class="crm-form-stack">
    <div class="crm-field">
      <label class="crm-label" for="categoriaId">Categoría</label>
      <select name="categoria_id" id="categoriaId" required class="crm-select">
        <option value="">Seleccionar…</option>
        <?php foreach ($categorias as $cat): ?>
          <option value="<?= (int) $cat["id"] ?>"><?= authEsc($cat["nombre"]) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="crm-field">
      <label class="crm-label" for="csvFile">Archivo CSV</label>
      <input type="file" name="csv" id="csvFile" accept=".csv,text/csv" required class="crm-file" />
    </div>

    <button type="submit" class="crm-btn crm-btn-accent" id="btnImport">Importar prospectos</button>
  </form>

  <div id="importResult" class="crm-mt-4 crm-hidden"></div>
</div>

<div class="crm-card crm-p-6 crm-max-w-xl crm-mt-4">
  <h2 class="crm-card__title">Crear categoría rápida</h2>
  <div class="crm-form-row">
    <input type="text" id="newCatNombre" placeholder="Nombre (ej. Spas)" class="crm-input" />
    <button type="button" id="btnNewCat" class="crm-btn crm-btn-primary">Crear</button>
  </div>
</div>

<script>
document.getElementById("importForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const btn = document.getElementById("btnImport");
  btn.disabled = true;
  btn.textContent = "Importando…";

  const fd = new FormData(e.target);
  const res = await fetch("api/import.php", { method: "POST", body: fd });
  const data = await res.json();

  const box = document.getElementById("importResult");
  box.classList.remove("crm-hidden");

  if (data.ok) {
    box.className = "crm-mt-4 crm-alert crm-alert--ok";
    box.innerHTML = `<strong>Importación completada</strong><br>
      Leídas: ${data.filas_leidas} · Nuevas: ${data.filas_nuevas} · Duplicados: ${data.duplicados} · Errores: ${data.errores}`;
    crmToast("CSV importado");
  } else {
    box.className = "crm-mt-4 crm-alert crm-alert--err";
    box.textContent = data.error || "Error";
  }

  btn.disabled = false;
  btn.textContent = "Importar prospectos";
});

document.getElementById("btnNewCat").addEventListener("click", async () => {
  const nombre = document.getElementById("newCatNombre").value.trim();
  if (!nombre) return;

  const res = await fetch("api/categories.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ action: "create", nombre }),
  });
  const data = await res.json();
  if (data.ok) {
    const sel = document.getElementById("categoriaId");
    const opt = document.createElement("option");
    opt.value = data.id;
    opt.textContent = nombre;
    opt.selected = true;
    sel.appendChild(opt);
    document.getElementById("newCatNombre").value = "";
    crmToast("Categoría creada");
  } else {
    alert(data.error || "Error");
  }
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . "/includes/layout.php";
