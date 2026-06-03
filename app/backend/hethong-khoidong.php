<?php

declare(strict_types=1);

use App\Backend\Core\Config;
use App\Backend\Core\Database;
use App\Backend\Core\Route\Router;
use App\Backend\Core\Middleware\AuthMiddleware;
use App\Backend\Core\Middleware\RoleMiddleware;

if (!defined('VTMS_ENTRY')) {
    http_response_code(403);
    exit('Forbidden');
}

define('APP_PATH', BASE_PATH . '/app');
define('BACKEND_PATH', APP_PATH . '/backend');
define('FRONTEND_PATH', APP_PATH . '/frontend');
define('VIEW_PATH', FRONTEND_PATH . '/views');
define('LAYOUT_PATH', FRONTEND_PATH . '/layout');
define('CONFIG_PATH', BACKEND_PATH . '/config');
define('PUBLIC_PATH', BASE_PATH . '/public');

$vtmsFileMapFile = BACKEND_PATH . '/hethong-bando-file.php';
$vtmsFileMap = is_file($vtmsFileMapFile) ? require $vtmsFileMapFile : [];

if (!is_array($vtmsFileMap)) {
    $vtmsFileMap = [];
}

$GLOBALS['VTMS_FILE_MAP'] = $vtmsFileMap;

$backendFile = static function (string $logicalPath) use ($vtmsFileMap): string {
    $mappedPath = $vtmsFileMap['backend'][$logicalPath] ?? $logicalPath;

    return BACKEND_PATH . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $mappedPath);
};

require $backendFile('core/helpers.php');

load_env_file(BASE_PATH . '/.env');

spl_autoload_register(static function (string $class) use ($vtmsFileMap): void {
    $prefix = 'App\\Backend\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    if (isset($vtmsFileMap['autoload'][$class])) {
        $mappedFile = BACKEND_PATH . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $vtmsFileMap['autoload'][$class]);

        if (is_file($mappedFile)) {
            require $mappedFile;
        }

        return;
    }

    $relative = substr($class, strlen($prefix));
    $parts = explode('\\', $relative);

    if ($parts === []) {
        return;
    }

    $parts[0] = match ($parts[0]) {
        'Controllers' => 'controllers',
        'Services' => 'services',
        'Models' => 'models',
        'Core' => 'core',
        default => $parts[0],
    };

    if ($parts[0] === 'core' && isset($parts[1])) {
        $parts[1] = match ($parts[1]) {
            'Auth' => 'auth',
            'Http' => 'http',
            'Middleware' => 'middleware',
            'Route' => 'route',
            default => $parts[1],
        };
    }

    $file = BACKEND_PATH . '/' . implode(DIRECTORY_SEPARATOR, $parts) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

Config::load(CONFIG_PATH, $vtmsFileMap['config'] ?? []);

date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Ho_Chi_Minh'));

if ((bool) Config::get('app.debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

session_name((string) Config::get('app.session_name', 'VTMS_SESSION'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

Database::configure((array) Config::get('database', []));

$router = new Router();
$router->aliasMiddleware('auth', AuthMiddleware::class);
$router->aliasMiddleware('role', RoleMiddleware::class);

$routes = require $backendFile('config/routes.php');
$routes($router);

return $router;
