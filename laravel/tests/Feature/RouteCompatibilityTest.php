<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class RouteCompatibilityTest extends TestCase
{
    public function test_shared_routes_exist(): void
    {
        $paths = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        foreach ([
            '/',
            'login',
            'logout',
            'dashboard',
            'tai-khoan/doi-mat-khau',
            'account/change-password',
            'api/auth/login',
            'api/auth/logout',
            'api/auth/me',
            'api/account/password',
            'api/auth/change-password',
        ] as $path) {
            $this->assertContains($path, $paths);
        }
    }
}
