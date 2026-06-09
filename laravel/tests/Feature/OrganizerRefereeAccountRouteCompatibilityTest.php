<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class OrganizerRefereeAccountRouteCompatibilityTest extends TestCase
{
    private function organizerSession(array $overrides = [], array $organizerOverrides = []): array
    {
        $organizer = array_merge([
            'idbantochuc' => 10,
            'idkhuvucquanly' => 1,
            'capkhuvucquanly' => 'QUOC_GIA',
            'iddonvi' => 1,
            'madonvi' => 'LDVN',
            'tendonvi' => 'Lien doan',
            'maloaidonvi' => 'LIEN_DOAN_BONG_CHUYEN_VN',
            'can_higher_eligibility' => true,
            'can_approve_coach_accounts' => true,
            'can_approve_referee_accounts' => true,
        ], $organizerOverrides);

        return [
            'auth_user' => array_merge([
                'id' => 2,
                'username' => 'organizer_test',
                'name' => 'Organizer Test',
                'email' => 'organizer@example.test',
                'role' => 'BAN_TO_CHUC',
                'organizer' => $organizer,
            ], $overrides),
        ];
    }

    public function test_organizer_referee_account_routes_exist(): void
    {
        $paths = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        foreach ([
            'ban-to-chuc/tai-khoan-trong-tai',
            'api/organizer/referee-accounts',
            'api/organizer/referee-accounts/{accountId}',
            'api/organizer/referee-accounts/{accountId}/approve',
            'api/organizer/referee-accounts/{accountId}/reject',
        ] as $path) {
            $this->assertContains($path, $paths);
        }
    }

    public function test_organizer_referee_account_page_requires_login(): void
    {
        $this->get('/ban-to-chuc/tai-khoan-trong-tai')->assertRedirect('/login');
    }

    public function test_organizer_referee_account_api_requires_login(): void
    {
        $this->getJson('/api/organizer/referee-accounts')->assertStatus(401)->assertJson([
            'success' => false,
        ]);
    }

    public function test_organizer_referee_account_page_rejects_non_organizer_role(): void
    {
        $this->withSession($this->organizerSession(['role' => 'HUAN_LUYEN_VIEN']))
            ->get('/ban-to-chuc/tai-khoan-trong-tai')
            ->assertStatus(403);
    }

    public function test_organizer_referee_account_page_rejects_organizer_without_approval_scope(): void
    {
        $this->withSession($this->organizerSession([], ['can_approve_referee_accounts' => false]))
            ->get('/ban-to-chuc/tai-khoan-trong-tai')
            ->assertStatus(403);
    }

    public function test_organizer_referee_account_page_renders_for_authorized_organizer_session(): void
    {
        $this->withSession($this->organizerSession())
            ->get('/ban-to-chuc/tai-khoan-trong-tai')
            ->assertOk()
            ->assertSee('Duyet tai khoan trong tai')
            ->assertSee('/api/organizer/referee-accounts');
    }
}
