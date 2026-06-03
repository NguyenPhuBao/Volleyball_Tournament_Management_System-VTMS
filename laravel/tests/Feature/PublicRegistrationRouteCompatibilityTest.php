<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PublicRegistrationRouteCompatibilityTest extends TestCase
{
    public function test_public_registration_routes_exist(): void
    {
        $paths = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        foreach ([
            'huanluyenvien/dang-ky',
            'api/coach/register/options',
            'api/auth/register/coach',
            'api/register/coach',
            'api/coach/register',
            'api/coaches/register',
            'api/huan-luyen-vien/register',
            'api/huanluyenvien/register',
            'referee/register',
            'trong-tai/dang-ky',
            'trongtai/dang-ky',
            'api/referee/register/options',
            'api/auth/register/referee',
            'api/register/referee',
            'api/referee/register',
            'api/referees/register',
            'api/trong-tai/register',
            'api/trongtai/register',
        ] as $path) {
            $this->assertContains($path, $paths);
        }
    }

    public function test_public_registration_pages_render(): void
    {
        $this->get('/huanluyenvien/dang-ky')
            ->assertOk()
            ->assertSee('Dang ky tai khoan Huan luyen vien');

        $this->get('/trong-tai/dang-ky')
            ->assertOk()
            ->assertSee('Dang ky tai khoan Trong tai');
    }

    public function test_coach_registration_rejects_empty_payload(): void
    {
        $this->postJson('/api/coach/register', [])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Du lieu dang ky huan luyen vien khong hop le.',
            ])
            ->assertJsonStructure(['errors' => ['username', 'email', 'password', 'hodem', 'ten', 'gioitinh']]);
    }

    public function test_referee_registration_rejects_empty_payload(): void
    {
        $this->postJson('/api/referee/register', [])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Du lieu dang ky trong tai khong hop le.',
            ])
            ->assertJsonStructure(['errors' => ['username', 'email', 'password', 'hodem', 'ten', 'gioitinh', 'capbac']]);
    }
}
