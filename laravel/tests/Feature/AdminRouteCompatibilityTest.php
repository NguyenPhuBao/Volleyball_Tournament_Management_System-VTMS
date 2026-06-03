<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AdminRouteCompatibilityTest extends TestCase
{
    private function adminSession(array $overrides = []): array
    {
        return [
            'auth_user' => array_merge([
                'id' => 1,
                'username' => 'admin_test',
                'name' => 'Admin Test',
                'email' => 'admin@example.test',
                'role' => 'ADMIN',
            ], $overrides),
        ];
    }

    public function test_admin_routes_exist(): void
    {
        $paths = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        foreach ([
            'admin',
            'admin/users',
            'admin/nguoi-dung',
            'admin/logs',
            'admin/xac-nhan-thong-tin-btc',
            'api/admin/roles',
            'api/admin/accounts',
            'api/admin/accounts/{id}',
            'api/admin/accounts/{id}/update',
            'api/admin/accounts/{id}/delete',
            'api/admin/users',
            'api/admin/users/{id}',
            'api/admin/users/{id}/update',
            'api/admin/system-logs',
            'api/admin/system-logs/options',
            'api/admin/system-logs/{id}',
            'api/admin/organizer-change-requests',
            'api/admin/organizer-change-requests/{id}',
            'api/admin/organizer-change-requests/{id}/approve',
            'api/admin/organizer-change-requests/{id}/reject',
        ] as $path) {
            $this->assertContains($path, $paths);
        }
    }

    public function test_admin_web_routes_require_login(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_admin_api_routes_require_login(): void
    {
        $this->getJson('/api/admin/accounts')->assertStatus(401)->assertJson([
            'success' => false,
        ]);
    }

    public function test_admin_web_routes_reject_non_admin_role(): void
    {
        $this->withSession($this->adminSession(['role' => 'HUAN_LUYEN_VIEN']))
            ->get('/admin/users')
            ->assertStatus(403);
    }

    public function test_admin_pages_render_for_admin_session(): void
    {
        foreach ([
            '/admin' => 'Tong quan quan tri',
            '/admin/users' => 'Quan tri tai khoan',
            '/admin/nguoi-dung' => 'Ho so nguoi dung',
            '/admin/logs' => 'Nhat ky he thong',
            '/admin/xac-nhan-thong-tin-btc' => 'Xac nhan thong tin ban to chuc',
        ] as $path => $text) {
            $this->withSession($this->adminSession())
                ->get($path)
                ->assertOk()
                ->assertSee($text);
        }
    }
}
