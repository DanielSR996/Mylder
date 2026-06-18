<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/api/db.php";
require_once dirname(__DIR__) . "/crm/includes/presence.php";

$pdo = db();
echo "PHP tz: " . date_default_timezone_get() . "\n";
echo "PHP now: " . date("Y-m-d H:i:s") . "\n";
echo "MySQL now: " . $pdo->query("SELECT NOW() AS n")->fetch()["n"] . "\n\n";

$team = presenceFetchTeam();
foreach ($team as $u) {
  echo "{$u['nombre']}: " . ($u['online'] ? "ONLINE" : "offline") . " — {$u['ultima_vez']}\n";
  if ($u['online'] && $u['conectado_desde']) {
    echo "  {$u['conectado_desde']}\n";
  }
  echo "  ultima_actividad={$u['ultima_actividad']}\n\n";
}
