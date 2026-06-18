<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/includes/auth.php";
require_once dirname(__DIR__) . "/includes/schema.php";

$user = requireLogin();

try {
  $pdo = db();
  $isAdmin = $user["rol"] === "admin";
  $soloMios = isset($_GET["solo_mios"]) && $_GET["solo_mios"] === "1";
  $agentFilter = $soloMios
    ? " AND (p.asignado_a = :agent_id OR p.asignado_a IS NULL)"
    : "";
  $params = $soloMios ? ["agent_id" => $user["id"]] : [];
  $visibleSql = crmProspectOcultoSql("p");

  $stats = [];

  $sqlEstados = "
    SELECT p.estado, COUNT(*) AS total
    FROM prospectos p
    WHERE {$visibleSql} {$agentFilter}
    GROUP BY p.estado
  ";
  $stmt = $pdo->prepare($sqlEstados);
  $stmt->execute($params);
  $stats["por_estado"] = $stmt->fetchAll();

  $sqlCat = "
    SELECT c.nombre, COUNT(*) AS total,
      SUM(CASE WHEN p.estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
      SUM(CASE WHEN p.estado = 'interesado' THEN 1 ELSE 0 END) AS interesados
    FROM prospectos p
    JOIN categorias c ON c.id = p.categoria_id
    WHERE {$visibleSql} {$agentFilter}
    GROUP BY c.id, c.nombre
    ORDER BY c.nombre
  ";
  $stmt = $pdo->prepare($sqlCat);
  $stmt->execute($params);
  $stats["por_categoria"] = $stmt->fetchAll();

  $sqlCallback = "
    SELECT COUNT(*) FROM prospectos p
    WHERE p.fecha_callback IS NOT NULL AND DATE(p.fecha_callback) <= CURDATE()
    AND p.estado = 'pensando' AND {$visibleSql}
    {$agentFilter}
  ";
  $stmt = $pdo->prepare($sqlCallback);
  $stmt->execute($params);
  $stats["callbacks_vencidos"] = (int) $stmt->fetchColumn();

  $sqlReuniones = "
    SELECT COUNT(*) FROM prospectos p
    WHERE p.fecha_reunion IS NOT NULL
    AND DATE(p.fecha_reunion) >= CURDATE()
    AND DATE(p.fecha_reunion) <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    {$agentFilter}
  ";
  $stmt = $pdo->prepare($sqlReuniones);
  $stmt->execute($params);
  $stats["reuniones_proximas_7d"] = (int) $stmt->fetchColumn();

  $sqlHoy = "
    SELECT COUNT(*) FROM intentos_contacto i
    JOIN prospectos p ON p.id = i.prospecto_id
    WHERE DATE(i.creado_en) = CURDATE()
    " . ($isAdmin ? "" : " AND i.agente_id = :agent_id2");
  $paramsHoy = $isAdmin ? [] : ["agent_id2" => $user["id"]];
  $stmt = $pdo->prepare($sqlHoy);
  $stmt->execute($paramsHoy);
  $stats["contactos_hoy"] = (int) $stmt->fetchColumn();

  $sqlHoyList = "
    SELECT p.id, p.nombre, p.telefono, p.estado, p.fecha_callback, p.fecha_reunion, p.link_reunion, c.nombre AS categoria
    FROM prospectos p
    JOIN categorias c ON c.id = p.categoria_id
    WHERE p.estado = 'pensando'
      AND p.fecha_callback IS NOT NULL
      AND DATE(p.fecha_callback) <= CURDATE()
      AND {$visibleSql}
      {$agentFilter}
    ORDER BY p.fecha_callback ASC
    LIMIT 8
  ";
  $stmt = $pdo->prepare($sqlHoyList);
  $stmt->execute($params);
  $stats["callbacks_hoy_lista"] = $stmt->fetchAll();

  $sqlReuList = "
    SELECT p.id, p.nombre, p.telefono, p.fecha_reunion, p.link_reunion, c.nombre AS categoria
    FROM prospectos p
    JOIN categorias c ON c.id = p.categoria_id
    WHERE p.fecha_reunion IS NOT NULL
      AND DATE(p.fecha_reunion) >= CURDATE()
      AND DATE(p.fecha_reunion) <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
      AND {$visibleSql}
      {$agentFilter}
    ORDER BY p.fecha_reunion ASC
    LIMIT 8
  ";
  $stmt = $pdo->prepare($sqlReuList);
  $stmt->execute($params);
  $stats["reuniones_hoy_lista"] = $stmt->fetchAll();

  if ($isAdmin) {
    $stats["total_usuarios"] = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1")->fetchColumn();
    $stats["ultimas_importaciones"] = $pdo->query("
      SELECT i.*, c.nombre AS categoria, u.nombre AS importado
      FROM importaciones i
      JOIN categorias c ON c.id = i.categoria_id
      LEFT JOIN usuarios u ON u.id = i.importado_por
      ORDER BY i.creado_en DESC LIMIT 5
    ")->fetchAll();
  }

  $stats["organicos_activos"] = crmProspectOrganicosCount($pdo);

  authJson(["ok" => true, "stats" => $stats]);
} catch (Throwable $e) {
  authJson(["ok" => false, "error" => $e->getMessage()], 500);
}
