<?php
declare(strict_types=1);

/** @var list<array{id:int,nombre:string}> $categorias */
/** @var list<string> $ciudades */
/** @var string $estatusDefaultLabel */

if (!isset($categorias, $ciudades, $estatusDefaultLabel)) {
  throw new RuntimeException("prospect-filter-fields.php requiere \$categorias, \$ciudades y \$estatusDefaultLabel");
}
?>
<div class="crm-field">
  <label class="crm-label" for="filterCiudad">Ciudad / zona</label>
  <select id="filterCiudad" class="crm-select">
    <option value="">Todas</option>
    <?php foreach ($ciudades as $ciudad): ?>
      <option value="<?= authEsc($ciudad) ?>"><?= authEsc($ciudad) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="crm-field">
  <label class="crm-label" for="filterCategoria">Categoría</label>
  <select id="filterCategoria" class="crm-select">
    <option value="">Todas</option>
    <?php foreach ($categorias as $cat): ?>
      <option value="<?= (int) $cat["id"] ?>"><?= authEsc($cat["nombre"]) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="crm-field">
  <label class="crm-label" for="filterEstado">Estatus</label>
  <select id="filterEstado" class="crm-select">
    <option value=""><?= authEsc($estatusDefaultLabel) ?></option>
    <?php foreach (PROSPECTO_ESTADOS as $key => $label): ?>
      <option value="<?= authEsc($key) ?>"><?= authEsc($label) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="crm-field">
  <label class="crm-check" style="margin-top:1.6rem">
    <input type="checkbox" id="filterCallback" />
    Callbacks vencidos hoy
  </label>
</div>
<div class="crm-field">
  <label class="crm-check" style="margin-top:1.6rem">
    <input type="checkbox" id="filterOrganicos" />
    Solo orgánicos (web)
  </label>
</div>
<?php if (($user["rol"] ?? "") === "admin"): ?>
<div class="crm-field">
  <label class="crm-check" style="margin-top:1.6rem">
    <input type="checkbox" id="filterOcultos" />
    Incluir ocultos (3+ sin respuesta)
  </label>
</div>
<?php endif; ?>
