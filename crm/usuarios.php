<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
$user = requireRole("admin");

$title = "Usuarios";
$activeNav = "usuarios.php";
$pageSubtitle = "Administra agentes y permisos del equipo";

ob_start();
?>
<div class="crm-card crm-p-6 crm-max-w-xl crm-mb-4">
  <h2 class="crm-card__title">Nuevo usuario</h2>
  <form id="userForm" class="crm-form-stack" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
    <input name="nombre" placeholder="Nombre" required class="crm-input" />
    <input name="email" type="email" placeholder="email@mylder.mx" required class="crm-input" />
    <div class="crm-field" style="grid-column:span 1">
      <div class="crm-password-wrap">
        <input name="password" id="newUserPassword" type="password" placeholder="Contraseña (opcional)" minlength="8" class="crm-input crm-password-input" autocomplete="new-password" />
        <button type="button" class="crm-password-toggle" data-target="newUserPassword" aria-label="Mostrar contraseña">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        </button>
      </div>
      <p class="crm-field-hint">Si la dejas vacía, se enviará un correo para que el usuario active su cuenta y elija contraseña.</p>
    </div>
    <select name="rol" class="crm-select">
      <option value="agente">Agente</option>
      <option value="admin">Admin</option>
    </select>
    <button type="submit" class="crm-btn crm-btn-accent" style="grid-column:1/-1">Crear usuario</button>
  </form>
</div>

<div class="crm-card crm-table-wrap">
  <table class="crm-table">
    <thead>
      <tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody id="usersBody"><tr><td colspan="5" class="text-center py-6">Cargando…</td></tr></tbody>
  </table>
</div>

<script src="assets/auth.js?v=20260620"></script>
<script>
async function loadUsers() {
  const res = await fetch("api/users.php");
  const data = await res.json();
  const tbody = document.getElementById("usersBody");
  if (!data.ok) { tbody.innerHTML = "<tr><td colspan='5'>Error</td></tr>"; return; }

  tbody.innerHTML = data.data.map(u => `
    <tr>
      <td>${esc(u.nombre)}</td>
      <td>${esc(u.email)}</td>
      <td>${esc(u.rol)}</td>
      <td>${u.activo == 1 ? '<span class="text-green-600">Activo</span>' : '<span class="text-red-600">Bloqueado</span>'}</td>
      <td class="space-x-1">
        <button class="text-xs crm-btn crm-btn-ghost invite-btn" data-id="${u.id}">Reenviar invitación</button>
        <button class="text-xs crm-btn crm-btn-ghost toggle-btn" data-id="${u.id}" data-activo="${u.activo == 1 ? 0 : 1}">${u.activo == 1 ? 'Bloquear' : 'Activar'}</button>
        <button class="text-xs crm-btn crm-btn-ghost text-red-600 delete-btn" data-id="${u.id}">Eliminar</button>
      </td>
    </tr>
  `).join("");

  document.querySelectorAll(".invite-btn").forEach(btn => {
    btn.addEventListener("click", async () => {
      const res = await fetch("api/users.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "resend_invite", id: Number(btn.dataset.id) }),
      });
      const d = await res.json();
      if (d.ok) {
        crmToast(d.email_sent ? "Invitación enviada por correo" : "Usuario listo (revisa configuración de correo)");
        if (d.dev_link) {
          alert("Modo local — enlace de activación:\n\n" + d.dev_link);
        }
      } else {
        alert(d.error);
      }
    });
  });

  document.querySelectorAll(".toggle-btn").forEach(btn => {
    btn.addEventListener("click", async () => {
      await fetch("api/users.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "toggle", id: Number(btn.dataset.id), activo: Number(btn.dataset.activo) }),
      });
      loadUsers();
    });
  });

  document.querySelectorAll(".delete-btn").forEach(btn => {
    btn.addEventListener("click", async () => {
      if (!confirm("¿Eliminar usuario?")) return;
      const res = await fetch("api/users.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", id: Number(btn.dataset.id) }),
      });
      const d = await res.json();
      if (!d.ok) alert(d.error);
      loadUsers();
    });
  });
}

document.getElementById("userForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const res = await fetch("api/users.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      action: "create",
      nombre: fd.get("nombre"),
      email: fd.get("email"),
      password: fd.get("password") || "",
      rol: fd.get("rol"),
    }),
  });
  const data = await res.json();
  if (data.ok) {
    e.target.reset();
    if (data.invited) {
      if (data.dev_link) {
        alert("Modo local — enlace de activación:\n\n" + data.dev_link);
      }
      crmToast(data.email_sent ? "Usuario creado — correo de activación enviado" : "Usuario creado (modo local: revisa el enlace)");
    } else {
      crmToast("Usuario creado");
    }
    loadUsers();
  } else {
    alert(data.error);
  }
});

function esc(s) { const d = document.createElement("div"); d.textContent = s; return d.innerHTML; }
loadUsers();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . "/includes/layout.php";
