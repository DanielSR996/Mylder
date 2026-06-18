<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/api/db.php";
require_once dirname(__DIR__) . "/crm/includes/schema.php";
require_once dirname(__DIR__) . "/crm/includes/prospects.php";

$pdo = db();

echo "has_oculto: " . (crmSchemaHasColumn("prospectos", "oculto") ? "yes" : "no") . "\n";
echo "has_prioridad: " . (crmSchemaHasColumn("prospectos", "prioridad") ? "yes" : "no") . "\n";

try {
  $total = (int) $pdo->query("SELECT COUNT(*) FROM prospectos")->fetchColumn();
  echo "total prospectos: {$total}\n";

  $visibleSql = prospectVisibleSql("p");
  echo "visibleSql: {$visibleSql}\n";

  $countStmt = $pdo->prepare("SELECT COUNT(*) FROM prospectos p WHERE {$visibleSql}");
  $countStmt->execute();
  echo "visible count: " . (int) $countStmt->fetchColumn() . "\n";

  $sql = "
    SELECT " . prospectListSelectSql() . "
    FROM prospectos p
    " . prospectListJoinSql() . "
    WHERE {$visibleSql}
    ORDER BY p.prioridad DESC, p.id ASC
    LIMIT 3
  ";
  $rows = $pdo->query($sql)->fetchAll();
  echo "list query rows: " . count($rows) . "\n";
  foreach ($rows as $r) {
    echo " - {$r['id']} {$r['nombre']} oculto=" . ($r['oculto'] ?? '?') . "\n";
  }
} catch (Throwable $e) {
  echo "ERR: " . $e->getMessage() . "\n";
}
