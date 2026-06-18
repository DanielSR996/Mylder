<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/includes/auth.php";
require_once dirname(__DIR__, 2) . "/api/crm-lead-ingest.php";

$user = requireRole("admin");
$method = $_SERVER["REQUEST_METHOD"];

if ($method !== "GET") {
  authJson(["ok" => false, "error" => "Método no permitido"], 405);
}

$export = trim((string) ($_GET["export"] ?? ""));
$desde = trim((string) ($_GET["desde"] ?? ""));
$hasta = trim((string) ($_GET["hasta"] ?? ""));
$agenteId = (int) ($_GET["agente_id"] ?? 0);
$estado = trim((string) ($_GET["estado"] ?? ""));
$origen = trim((string) ($_GET["origen"] ?? ""));

try {
  if ($export === "csv_prospectos") {
    exportProspectosCsv($desde, $hasta, $agenteId, $estado, $origen);
  }
  if ($export === "csv_intentos") {
    exportIntentosCsv($desde, $hasta, $agenteId);
  }

  authJson(["ok" => true, "summary" => buildSummary($desde, $hasta, $agenteId)]);
} catch (Throwable $e) {
  authJson(["ok" => false, "error" => $e->getMessage()], 500);
}

function buildSummary(string $desde, string $hasta, int $agenteId): array {
  $pdo = db();
  [$dateSql, $dateParams] = dateFilterSql("p.creado_en", $desde, $hasta);

  $agentSql = $agenteId > 0 ? " AND p.asignado_a = ?" : "";
  $params = array_merge($dateParams, $agenteId > 0 ? [$agenteId] : []);

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM prospectos p WHERE 1=1 {$dateSql} {$agentSql}");
  $stmt->execute($params);
  $total = (int) $stmt->fetchColumn();

  $stmt = $pdo->prepare("
    SELECT COUNT(*) FROM prospectos p
    WHERE p.origen IN ('web_contacto', 'web_cita') {$dateSql} {$agentSql}
  ");
  $stmt->execute($params);
  $organicos = (int) $stmt->fetchColumn();

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM prospectos p WHERE p.oculto = 1 {$dateSql} {$agentSql}");
  $stmt->execute($params);
  $ocultos = (int) $stmt->fetchColumn();

  $stmt = $pdo->prepare("
    SELECT p.estado, COUNT(*) AS total FROM prospectos p
    WHERE 1=1 {$dateSql} {$agentSql}
    GROUP BY p.estado ORDER BY total DESC
  ");
  $stmt->execute($params);
  $porEstado = $stmt->fetchAll();

  [$intDateSql, $intDateParams] = dateFilterSql("i.creado_en", $desde, $hasta);
  $intAgentSql = $agenteId > 0 ? " AND i.agente_id = ?" : "";
  $intParams = array_merge($intDateParams, $agenteId > 0 ? [$agenteId] : []);

  $stmt = $pdo->prepare("
    SELECT u.nombre AS agente, COUNT(*) AS total
    FROM intentos_contacto i
    JOIN usuarios u ON u.id = i.agente_id
    WHERE 1=1 {$intDateSql} {$intAgentSql}
    GROUP BY i.agente_id, u.nombre
    ORDER BY total DESC
  ");
  $stmt->execute($intParams);
  $porAgente = $stmt->fetchAll();

  $stmt = $pdo->prepare("
    SELECT i.resultado, COUNT(*) AS total
    FROM intentos_contacto i
    WHERE 1=1 {$intDateSql} {$intAgentSql}
    GROUP BY i.resultado ORDER BY total DESC
  ");
  $stmt->execute($intParams);
  $porResultado = $stmt->fetchAll();

  return [
    "total_prospectos" => $total,
    "organicos" => $organicos,
    "ocultos" => $ocultos,
    "por_estado" => $porEstado,
    "intentos_por_agente" => $porAgente,
    "intentos_por_resultado" => $porResultado,
  ];
}

function dateFilterSql(string $column, string $desde, string $hasta): array {
  $parts = [];
  $params = [];
  if ($desde !== "" && preg_match("/^\d{4}-\d{2}-\d{2}$/", $desde)) {
    $parts[] = " AND DATE({$column}) >= ?";
    $params[] = $desde;
  }
  if ($hasta !== "" && preg_match("/^\d{4}-\d{2}-\d{2}$/", $hasta)) {
    $parts[] = " AND DATE({$column}) <= ?";
    $params[] = $hasta;
  }
  return [implode("", $parts), $params];
}

function exportProspectosCsv(string $desde, string $hasta, int $agenteId, string $estado, string $origen): never {
  $pdo = db();
  [$dateSql, $params] = dateFilterSql("p.creado_en", $desde, $hasta);

  if ($agenteId > 0) {
    $dateSql .= " AND p.asignado_a = ?";
    $params[] = $agenteId;
  }
  if ($estado !== "" && isset(PROSPECTO_ESTADOS[$estado])) {
    $dateSql .= " AND p.estado = ?";
    $params[] = $estado;
  }
  if ($origen !== "" && in_array($origen, ["csv", "web_contacto", "web_cita"], true)) {
    $dateSql .= " AND p.origen = ?";
    $params[] = $origen;
  }

  $stmt = $pdo->prepare("
    SELECT p.id, p.nombre, p.telefono, p.email, p.ciudad, p.estado, p.origen, p.prioridad,
           p.intentos, p.intentos_sin_respuesta, p.oculto, p.servicio_interes,
           c.nombre AS categoria, u.nombre AS agente, p.ultimo_contacto, p.creado_en
    FROM prospectos p
    JOIN categorias c ON c.id = p.categoria_id
    LEFT JOIN usuarios u ON u.id = p.asignado_a
    WHERE 1=1 {$dateSql}
    ORDER BY p.prioridad DESC, p.id ASC
  ");
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  header("Content-Type: text/csv; charset=utf-8");
  header("Content-Disposition: attachment; filename=\"prospectos_" . date("Ymd") . ".csv\"");
  echo "\xEF\xBB\xBF";
  $out = fopen("php://output", "w");
  fputcsv($out, [
    "ID", "Nombre", "Teléfono", "Email", "Ciudad", "Estado", "Origen", "Prioridad",
    "Intentos", "Sin respuesta", "Oculto", "Servicio interés", "Categoría", "Agente",
    "Último contacto", "Creado",
  ]);
  foreach ($rows as $r) {
    fputcsv($out, [
      $r["id"], $r["nombre"], $r["telefono"], $r["email"], $r["ciudad"],
      PROSPECTO_ESTADOS[$r["estado"]] ?? $r["estado"],
      crmProspectOrigenLabel((string) $r["origen"]),
      $r["prioridad"], $r["intentos"], $r["intentos_sin_respuesta"],
      (int) $r["oculto"] ? "Sí" : "No",
      $r["servicio_interes"], $r["categoria"], $r["agente"],
      $r["ultimo_contacto"], $r["creado_en"],
    ]);
  }
  fclose($out);
  exit;
}

function exportIntentosCsv(string $desde, string $hasta, int $agenteId): never {
  $pdo = db();
  [$dateSql, $params] = dateFilterSql("i.creado_en", $desde, $hasta);

  if ($agenteId > 0) {
    $dateSql .= " AND i.agente_id = ?";
    $params[] = $agenteId;
  }

  $stmt = $pdo->prepare("
    SELECT i.creado_en, u.nombre AS agente, p.nombre AS prospecto, p.telefono,
           i.resultado, i.notas, p.origen
    FROM intentos_contacto i
    JOIN usuarios u ON u.id = i.agente_id
    JOIN prospectos p ON p.id = i.prospecto_id
    WHERE 1=1 {$dateSql}
    ORDER BY i.creado_en DESC
  ");
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  header("Content-Type: text/csv; charset=utf-8");
  header("Content-Disposition: attachment; filename=\"historial_llamadas_" . date("Ymd") . ".csv\"");
  echo "\xEF\xBB\xBF";
  $out = fopen("php://output", "w");
  fputcsv($out, ["Fecha", "Agente", "Prospecto", "Teléfono", "Resultado", "Notas", "Origen prospecto"]);
  foreach ($rows as $r) {
    fputcsv($out, [
      $r["creado_en"],
      $r["agente"],
      $r["prospecto"],
      $r["telefono"],
      PROSPECTO_ESTADOS[$r["resultado"]] ?? $r["resultado"],
      $r["notas"],
      crmProspectOrigenLabel((string) $r["origen"]),
    ]);
  }
  fclose($out);
  exit;
}
