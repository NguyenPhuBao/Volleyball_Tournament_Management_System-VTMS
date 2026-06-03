<?php

declare(strict_types=1);

define('VTMS_ENTRY', true);
define('BASE_PATH', dirname(__DIR__));

$router = require BASE_PATH . '/app/backend/hethong-khoidong.php';
$router->dispatch();
