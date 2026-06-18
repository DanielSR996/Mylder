<?php
declare(strict_types=1);
$_SERVER['REQUEST_METHOD'] = 'GET';
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_nombre'] = 'Test';
$_SESSION['user_email'] = 't@test.com';
$_SESSION['user_rol'] = 'admin';
$_SESSION['must_change_password'] = false;
include __DIR__ . '/../crm/api/cotizaciones.php';
