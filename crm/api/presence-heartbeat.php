<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/includes/auth.php";
require_once dirname(__DIR__) . "/includes/presence.php";

$user = requireLogin();
authTouchPresence(true);

authJson(["ok" => true, "ts" => time()]);
