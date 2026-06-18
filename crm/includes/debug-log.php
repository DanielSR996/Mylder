<?php
declare(strict_types=1);

function crmDebugLog(string $location, string $message, array $data, string $hypothesisId): void {
  // #region agent log
  $line = json_encode([
    "sessionId" => "ab4a6a",
    "timestamp" => (int) round(microtime(true) * 1000),
    "location" => $location,
    "message" => $message,
    "data" => $data,
    "hypothesisId" => $hypothesisId,
  ], JSON_UNESCAPED_UNICODE);
  if ($line !== false) {
    file_put_contents(dirname(__DIR__, 2) . "/debug-ab4a6a.log", $line . "\n", FILE_APPEND | LOCK_EX);
  }
  // #endregion
}
