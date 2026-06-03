<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'VTMS'),
    'env' => env('APP_ENV', 'local'),
    'debug' => env_bool('APP_DEBUG', true),
    'url' => rtrim((string) env('APP_URL', ''), '/'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    'session_name' => env('APP_SESSION_NAME', 'VTMS_SESSION'),
];
