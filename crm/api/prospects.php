<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/includes/auth.php";
require_once dirname(__DIR__) . "/includes/prospects.php";
require_once dirname(__DIR__, 2) . "/api/crm-lead-ingest.php";

$user = requireLogin();
$method = $_SERVER["REQUEST_METHOD"];

try {
  if ($method === "GET") {
    routeGet($user);
  } elseif ($method === "POST") {
    handleMark($user);
  } else {
    authJson(["ok" => false, "error" => "Método no permitido"], 405);
  }
} catch (Throwable $e) {
  authJson(["ok" => false, "error" => $e->getMessage()], 500);
}

function routeGet(array $user): void {
  if (isset($_GET["id"])) {
    handleDetail($user, (int) $_GET["id"]);
    return;
  }
  if (isset($_GET["mode"]) && $_GET["mode"] === "next") {
    handleNext($user);
    return;
  }
  $q = trim((string) ($_GET["q"] ?? ""));
  if ($q !== "") {
    handleSearch($user, $q);
    return;
  }
  handleList($user);
}

function handleDetail(array $user, int $id): void {
  if ($id <= 0) {
    authJson(["ok" => false, "error" => "ID inválido"], 422);
  }

  $stmt = db()->prepare("
    SELECT p.*, c.nombre AS categoria_nombre, u.nombre AS agente_nombre
    FROM prospectos p
    JOIN categorias c ON c.id = p.categoria_id
    LEFT JOIN usuarios u ON u.id = p.asignado_a
    WHERE p.id = ?
  ");
  $stmt->execute([$id]);
  $row = $stmt->fetch();

  if (!prospectCanAccess($user, $row ?: null)) {
    authJson(["ok" => false, "error" => "Prospecto no encontrado"], 404);
  }

  authJson([
    "ok" => true,
    "prospecto" => $row,
    "historial" => prospectHistorialFor($id),
    "links" => [
      "tel" => prospectPhoneTel((string) $row["telefono"]),
      "whatsapp" => prospectPhoneWhatsApp((string) $row["telefono"]),
    ],
  ]);
}

function handleNext(array $user): void {
  [$agentSql, $agentParams] = prospectAgentFilter($user);
  [$filterSql, $filterParams] = prospectFiltersFromInput($user, $_GET, true);
  $excludeId = (int) ($_GET["exclude_id"] ?? 0);
  $excludeSql = $excludeId > 0 ? " AND p.id != ?" : "";
  $params = array_merge($filterParams, $agentParams, $excludeId > 0 ? [$excludeId] : []);

  $sql = "
    SELECT p.*, c.nombre AS categoria_nombre
    FROM prospectos p
    JOIN categorias c ON c.id = p.categoria_id
    WHERE {$filterSql} {$agentSql} {$excludeSql}
    " . prospectNextOrderSql() . "
    LIMIT 1
  ";

  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  $row = $stmt->fetch();

  authJson([
    "ok" => true,
    "prospecto" => $row ?: null,
    "historial" => $row ? prospectHistorialFor((int) $row["id"], 12) : [],
    "links" => $row ? [
      "tel" => prospectPhoneTel((string) $row["telefono"]),
      "whatsapp" => prospectPhoneWhatsApp((string) $row["telefono"]),
    ] : null,
  ]);
}

function handleSearch(array $user, string $q): void {
  $page = max(1, (int) ($_GET["page"] ?? 1));
  $perPage = min(50, max(10, (int) ($_GET["per_page"] ?? 20)));
  $offset = ($page - 1) * $perPage;

  [$agentSql, $agentParams] = prospectAgentFilter($user);
  $like = "%{$q}%";
  $digits = prospectPhoneDigits($q);

  $phoneSql = $digits !== "" ? " OR p.telefono_normalizado LIKE ?" : "";
  $params = array_merge($agentParams, [$like, $like, $like]);
  if ($digits !== "") {
    $params[] = "%{$digits}%";
  }

  $where = "(p.nombre LIKE ? OR p.ciudad LIKE ? OR p.telefono LIKE ?{$phoneSql}) AND " . prospectVisibleSql("p") . " {$agentSql}";

  $countStmt = db()->prepare("SELECT COUNT(*) FROM prospectos p WHERE {$where}");
  $countStmt->execute($params);
  $total = (int) $countStmt->fetchColumn();

  $sql = "
    SELECT p.*, c.nombre AS categoria_nombre
    FROM prospectos p
    JOIN categorias c ON c.id = p.categoria_id
    WHERE {$where}
    ORDER BY p.nombre ASC
    LIMIT {$perPage} OFFSET {$offset}
  ";
  $stmt = db()->prepare($sql);
  $stmt->execute($params);

  authJson([
    "ok" => true,
    "data" => $stmt->fetchAll(),
    "pagination" => [
      "page" => $page,
      "per_page" => $perPage,
      "total" => $total,
      "pages" => (int) ceil(max(1, $total) / $perPage),
    ],
  ]);
}

function handleList(array $user): void {
  $page = max(1, (int) ($_GET["page"] ?? 1));
  $perPage = min(100, max(10, (int) ($_GET["per_page"] ?? 25)));
  $offset = ($page - 1) * $perPage;

  [$whereSql, $params] = prospectFiltersFromInput($user, $_GET, false);

  $countStmt = db()->prepare("SELECT COUNT(*) FROM prospectos p WHERE {$whereSql}");
  $countStmt->execute($params);
  $total = (int) $countStmt->fetchColumn();

  $sql = "
    SELECT " . prospectListSelectSql() . "
    FROM prospectos p
    " . prospectListJoinSql() . "
    WHERE {$whereSql}
    ORDER BY p.prioridad DESC,
      CASE WHEN p.estado = 'pendiente' THEN 0 ELSE 1 END,
      p.fecha_callback ASC,
      p.id ASC
    LIMIT {$perPage} OFFSET {$offset}
  ";

  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  authJson([
    "ok" => true,
    "data" => $rows,
    "pagination" => [
      "page" => $page,
      "per_page" => $perPage,
      "total" => $total,
      "pages" => (int) ceil($total / $perPage),
    ],
  ]);
}

function handleMark(array $user): void {
  $body = authReadJsonBody();
  $prospectoId = (int) ($body["prospecto_id"] ?? 0);
  $resultado = trim((string) ($body["resultado"] ?? ""));
  $notas = trim((string) ($body["notas"] ?? ""));
  $fechaCallback = trim((string) ($body["fecha_callback"] ?? ""));
  $fechaReunion = trim((string) ($body["fecha_reunion"] ?? ""));
  $linkReunion = trim((string) ($body["link_reunion"] ?? ""));

  if ($prospectoId <= 0) {
    authJson(["ok" => false, "error" => "prospecto_id inválido"], 422);
  }

  if (!isset(PROSPECTO_ESTADOS[$resultado])) {
    authJson(["ok" => false, "error" => "resultado inválido"], 422);
  }

  if ($resultado === "pensando" && $fechaCallback === "") {
    authJson(["ok" => false, "error" => "fecha_callback es obligatoria para 'pensando'"], 422);
  }

  if ($resultado === "reunion_agendada" && $fechaReunion === "") {
    authJson(["ok" => false, "error" => "fecha_reunion es obligatoria para 'reunion_agendada'"], 422);
  }

  $pdo = db();
  $pdo->beginTransaction();

  try {
    $lock = $pdo->prepare("SELECT id, asignado_a FROM prospectos WHERE id = ? FOR UPDATE");
    $lock->execute([$prospectoId]);
    $prospecto = $lock->fetch();

    if (!$prospecto) {
      throw new RuntimeException("Prospecto no encontrado");
    }

    if (!prospectCanAccess($user, $prospecto)) {
      throw new RuntimeException("Este prospecto está asignado a otro agente");
    }

    $callbackVal = $resultado === "pensando" ? normalizeDatetime($fechaCallback) : null;
    $reunionVal = $resultado === "reunion_agendada" ? normalizeDatetime($fechaReunion) : null;
    $linkVal = $resultado === "reunion_agendada" && $linkReunion !== "" ? $linkReunion : null;

    if ($resultado !== "pensando") {
      $callbackVal = null;
    }
    if ($resultado !== "reunion_agendada") {
      $reunionVal = null;
      $linkVal = null;
    }

    $update = $pdo->prepare("
      UPDATE prospectos SET
        estado = ?,
        fecha_callback = ?,
        fecha_reunion = ?,
        link_reunion = ?,
        ultimo_contacto = NOW(),
        intentos = intentos + 1,
        asignado_a = COALESCE(asignado_a, ?)
      WHERE id = ?
    ");
    $update->execute([
      $resultado,
      $callbackVal,
      $reunionVal,
      $linkVal,
      $user["id"],
      $prospectoId,
    ]);

    $insert = $pdo->prepare("
      INSERT INTO intentos_contacto (prospecto_id, agente_id, resultado, notas)
      VALUES (?, ?, ?, ?)
    ");
    $insert->execute([$prospectoId, $user["id"], $resultado, $notas !== "" ? $notas : null]);

    crmProspectApplyMarkSideEffects($pdo, $prospectoId, $resultado);

    $statusRow = $pdo->prepare("SELECT oculto, intentos_sin_respuesta FROM prospectos WHERE id = ?");
    $statusRow->execute([$prospectoId]);
    $statusAfter = $statusRow->fetch();

    $pdo->commit();

    $response = [
      "ok" => true,
      "message" => "Estado actualizado",
      "agente" => $user["nombre"],
      "intentos_sin_respuesta" => (int) ($statusAfter["intentos_sin_respuesta"] ?? 0),
      "oculto" => (int) ($statusAfter["oculto"] ?? 0) === 1,
    ];

    if (!empty($response["oculto"])) {
      $response["message"] = "Registrado. Tras 3 intentos sin respuesta el prospecto se ocultó de la cola.";
    }

    if (!empty($body["fetch_next"])) {
      $filterInput = is_array($body["filters"] ?? null) ? $body["filters"] : $_GET;
      [$filterSql, $filterParams] = prospectFiltersFromInput($user, $filterInput, true);
      [$agentSql, $agentParams] = prospectAgentFilter($user);
      $params = array_merge($filterParams, $agentParams, [$prospectoId]);
      $sql = "
        SELECT p.*, c.nombre AS categoria_nombre
        FROM prospectos p
        JOIN categorias c ON c.id = p.categoria_id
        WHERE {$filterSql} {$agentSql} AND p.id != ?
        " . prospectNextOrderSql() . "
        LIMIT 1
      ";
      $nextStmt = db()->prepare($sql);
      $nextStmt->execute($params);
      $next = $nextStmt->fetch();
      $response["next"] = $next ?: null;
      $response["links"] = $next ? [
        "tel" => prospectPhoneTel((string) $next["telefono"]),
        "whatsapp" => prospectPhoneWhatsApp((string) $next["telefono"]),
      ] : null;
    }

    authJson($response);
  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
}

function normalizeDatetime(string $value): string {
  $value = str_replace("T", " ", trim($value));
  $ts = strtotime($value);
  if ($ts === false) {
    throw new RuntimeException("Fecha/hora inválida: {$value}");
  }
  return date("Y-m-d H:i:s", $ts);
}
