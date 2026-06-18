<?php
declare(strict_types=1);
// CLI test harness for stats API
$_SERVER['REQUEST_METHOD'] = 'GET';
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_nombre'] = 'Test';
$_SESSION['user_email'] = 'test@test.com';
$_SESSION['user_rol'] = 'admin';
$_SESSION['must_change_password'] = false;

ob_start();
try {
  include __DIR__ . '/../crm/api/stats.php';
} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
$out = ob_get_clean();
echo $out;
