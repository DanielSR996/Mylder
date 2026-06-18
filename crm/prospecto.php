<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/prospects.php";

$user = requireLogin();
$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {
  header("Location: queue.php");
  exit;
}

$stmt = db()->prepare("
  SELECT p.*, c.nombre AS categoria_nombre, u.nombre AS agente_nombre
  FROM prospectos p
  JOIN categorias c ON c.id = p.categoria_id
  LEFT JOIN usuarios u ON u.id = p.asignado_a
  WHERE p.id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!prospectCanAccess($user, $p ?: null)) {
  http_response_code(404);
  echo "Prospecto no encontrado";
  exit;
}

$hist = db()->prepare("
  SELECT i.*, u.nombre AS agente_nombre
  FROM intentos_contacto i
  JOIN usuarios u ON u.id = i.agente_id
  WHERE i.prospecto_id = ?
  ORDER BY i.creado_en DESC
");
$hist->execute([$id]);
$historial = $hist->fetchAll();

$tel = prospectPhoneTel((string) $p["telefono"]);
$wa = prospectPhoneWhatsApp((string) $p["telefono"]);

$title = (string) $p["nombre"];
$activeNav = "queue.php";
$pageSubtitle = authEsc((string) ($p["categoria_nombre"] ?? "")) . " · " . (PROSPECTO_ESTADOS[$p["estado"]] ?? $p["estado"]);

ob_start();
?>
<div class="crm-grid-2" style="align-items:start">
  <div class="crm-card crm-p-5">
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1rem">
      <span class="status-badge status-<?= authEsc($p["estado"]) ?>"><?= authEsc(PROSPECTO_ESTADOS[$p["estado"]] ?? $p["estado"]) ?></span>
      <?php if (prospectOrigenBadge($p["origen"] ?? "csv")): ?>
        <span class="crm-badge-organic"><?= authEsc(prospectOrigenBadge($p["origen"] ?? "csv")) ?></span>
      <?php endif; ?>
      <?php if ($p["agente_nombre"]): ?>
        <span class="crm-list-row__badge">Asignado: <?= authEsc($p["agente_nombre"]) ?></span>
      <?php endif; ?>
      <?php if ((int) ($p["intentos_sin_respuesta"] ?? 0) > 0): ?>
        <span class="crm-list-row__badge">Sin respuesta: <?= (int) $p["intentos_sin_respuesta"] ?>/3</span>
      <?php endif; ?>
    </div>

    <?php if (prospectIsOrganic($p["origen"] ?? "csv")): ?>
    <div class="crm-organic-context-box crm-mb-4">
      <h3 class="crm-organic-context-box__title">Lo que llenó en el formulario web</h3>
      <dl class="crm-organic-context-box__list">
        <?php foreach (prospectWebContextLines($p) as $line): ?>
          <div class="crm-organic-context-box__row">
            <dt><?= authEsc($line["label"]) ?></dt>
            <dd><?= nl2br(authEsc($line["value"])) ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>
    </div>
    <?php endif; ?>

    <?php if (trim((string) ($p["notas"] ?? "")) !== ""): ?>
    <div class="crm-bitacora-box crm-mb-4">
      <p class="crm-bitacora-box__heading">Bitácora · notas del prospecto</p>
      <p class="crm-bitacora-box__text"><?= nl2br(authEsc((string) $p["notas"])) ?></p>
    </div>
    <?php endif; ?>

    <dl class="crm-dl">
      <?php if (!empty($p["email"]) && !prospectIsOrganic($p["origen"] ?? "csv")): ?>
        <dt>Email</dt><dd><a href="mailto:<?= authEsc($p["email"]) ?>" class="crm-link"><?= authEsc($p["email"]) ?></a></dd>
      <?php endif; ?>
      <?php if (!empty($p["servicio_interes"]) && !prospectIsOrganic($p["origen"] ?? "csv")): ?>
        <dt>Servicio de interés</dt><dd><?= authEsc($p["servicio_interes"]) ?></dd>
      <?php endif; ?>
      <?php if (!empty($p["mensaje_web"]) && !prospectIsOrganic($p["origen"] ?? "csv")): ?>
        <dt>Mensaje web</dt><dd><?= nl2br(authEsc($p["mensaje_web"])) ?></dd>
      <?php endif; ?>
      <?php if ($p["ciudad"]): ?>
        <dt>Ciudad</dt><dd><?= authEsc($p["ciudad"]) ?></dd>
      <?php endif; ?>
      <dt>Teléfono</dt>
      <dd>
        <?php if ($tel): ?>
          <a href="tel:<?= authEsc(str_replace(" ", "", $tel)) ?>" class="crm-link"><?= authEsc($p["telefono"]) ?></a>
        <?php else: ?>
          <?= authEsc($p["telefono"]) ?>
        <?php endif; ?>
      </dd>
      <?php if ($p["direccion"]): ?>
        <dt>Dirección</dt><dd><?= authEsc($p["direccion"]) ?></dd>
      <?php endif; ?>
      <?php if ($p["calificacion"]): ?>
        <dt>Calificación Google</dt><dd>★ <?= authEsc((string) $p["calificacion"]) ?> (<?= (int) $p["num_resenas"] ?> reseñas)</dd>
      <?php endif; ?>
      <?php if ($p["fecha_callback"]): ?>
        <dt>Callback</dt><dd><?= authEsc($p["fecha_callback"]) ?></dd>
      <?php endif; ?>
      <?php if ($p["fecha_reunion"]): ?>
        <dt>Reunión</dt><dd><?= authEsc($p["fecha_reunion"]) ?></dd>
      <?php endif; ?>
      <?php if ($p["link_reunion"]): ?>
        <dt>Link reunión</dt><dd><a href="<?= authEsc($p["link_reunion"]) ?>" class="crm-link" target="_blank" rel="noopener">Abrir</a></dd>
      <?php endif; ?>
      <dt>Total intentos</dt><dd><?= (int) ($p["intentos"] ?? 0) ?> registrados en historial</dd>
    </dl>

    <div class="crm-dialer-actions-row" style="margin-top:1.25rem">
      <?php if ($tel): ?>
        <a href="tel:<?= authEsc(str_replace(" ", "", $tel)) ?>" class="crm-btn crm-btn-accent crm-btn--block">Llamar</a>
      <?php endif; ?>
      <?php if ($wa): ?>
        <a href="<?= authEsc($wa) ?>" target="_blank" rel="noopener" class="crm-btn crm-btn-ghost crm-btn--block">WhatsApp</a>
      <?php endif; ?>
      <?php if ($p["link_google_maps"]): ?>
        <a href="<?= authEsc($p["link_google_maps"]) ?>" target="_blank" rel="noopener" class="crm-btn crm-btn-ghost crm-btn--block">Google Maps</a>
      <?php endif; ?>
      <a href="llamar.php" class="crm-btn crm-btn-primary crm-btn--block">Modo llamada</a>
      <a href="queue.php" class="crm-btn crm-btn-ghost crm-btn--block">Volver a prospectos</a>
    </div>
  </div>

  <div class="crm-card crm-p-5">
    <h2 class="crm-card__title">Bitácora de contactos</h2>
    <?php if (!$historial): ?>
      <p class="crm-empty">Sin intentos registrados aún.</p>
    <?php else: ?>
      <ul class="crm-timeline">
        <?php foreach ($historial as $h): ?>
          <li class="crm-timeline__item">
            <div class="crm-timeline__dot"></div>
            <div class="crm-timeline__body">
              <div class="crm-timeline__head">
                <span class="status-badge status-<?= authEsc($h["resultado"]) ?>"><?= authEsc(PROSPECTO_ESTADOS[$h["resultado"]] ?? $h["resultado"]) ?></span>
                <time class="crm-timeline__time"><?= authEsc($h["creado_en"]) ?></time>
              </div>
              <p class="crm-timeline__agent"><?= authEsc($h["agente_nombre"]) ?></p>
              <?php if ($h["notas"]): ?>
                <p class="crm-timeline__notes"><?= nl2br(authEsc($h["notas"])) ?></p>
              <?php else: ?>
                <p class="crm-timeline__notes crm-bitacora-box__entry-note--muted">Sin notas en este intento</p>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . "/includes/layout.php";
