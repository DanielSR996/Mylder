<?php
declare(strict_types=1);

function crmSchemaHasColumn(string $table, string $column): bool {
  static $cache = [];
  $key = $table . "." . $column;
  if (array_key_exists($key, $cache)) {
    return $cache[$key];
  }
  try {
    db()->query("SELECT `{$column}` FROM `{$table}` LIMIT 0");
    $cache[$key] = true;
  } catch (Throwable) {
    $cache[$key] = false;
  }
  return $cache[$key];
}

function crmProspectOcultoSql(string $alias = "p"): string {
  return crmSchemaHasColumn("prospectos", "oculto") ? "{$alias}.oculto = 0" : "1=1";
}

function crmProspectOrganicosCount(PDO $pdo): int {
  if (!crmSchemaHasColumn("prospectos", "origen")) {
    return 0;
  }
  $ocultoSql = crmSchemaHasColumn("prospectos", "oculto") ? " AND oculto = 0" : "";
  return (int) $pdo->query("
    SELECT COUNT(*) FROM prospectos
    WHERE origen IN ('web_contacto', 'web_cita'){$ocultoSql}
      AND estado NOT IN ('no_interesa', 'convertido')
  ")->fetchColumn();
}
