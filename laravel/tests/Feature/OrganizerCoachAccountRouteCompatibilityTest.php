<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class OrganizerCoachAccountRouteCompatibilityTest extends TestCase
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

    public function test_organizer_coach_account_routes_exist(): void
    {
        $paths = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        foreach ([
            'ban-to-chuc/tai-khoan-hlv',
            'api/organizer/coach-accounts',
            'api/organizer/coach-accounts/{accountId}',
            'api/organizer/coach-accounts/{accountId}/approve',
            'api/organizer/coach-accounts/{accountId}/reject',
        ] as $path) {
            $this->assertContains($path, $paths);
        }
    }

    public function test_organizer_coach_account_page_requires_login(): void
    {
        $this->get('/ban-to-chuc/tai-khoan-hlv')->assertRedirect('/login');
    }

    public function test_organizer_coach_account_api_requires_login(): void
    {
        $this->getJson('/api/organizer/coach-accounts')->assertStatus(401)->assertJson([
            'success' => false,
        ]);
    }

    public function test_organizer_coach_account_page_rejects_non_organizer_role(): void
    {
        $this->withSession($this->organizerSession(['role' => 'HUAN_LUYEN_VIEN']))
            ->get('/ban-to-chuc/tai-khoan-hlv')
            ->assertStatus(403);
    }

    public function test_organizer_coach_account_page_rejects_organizer_without_approval_scope(): void
    {
        $this->withSession($this->organizerSession([], ['can_approve_coach_accounts' => false]))
            ->get('/ban-to-chuc/tai-khoan-hlv')
            ->assertStatus(403);
    }

    public function test_organizer_coach_account_page_renders_for_authorized_organizer_session(): void
    {
        $this->withSession($this->organizerSession())
            ->get('/ban-to-chuc/tai-khoan-hlv')
            ->assertOk()
            ->assertSee('Duyet tai khoan HLV')
            ->assertSee('/api/organizer/coach-accounts');
    }
}
