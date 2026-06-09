<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class OrganizerVenueRouteCompatibilityTest extends TestCase
{
    private function organizerSession(array $overrides = []): array
    {
        return [
            'auth_user' => array_merge([
                'id' => 2,
                'username' => 'organizer_test',
                'name' => 'Organizer Test',
                'email' => 'organizer@example.test',
                'role' => 'BAN_TO_CHUC',
                'organizer' => [
                    'idbantochuc' => 10,
                    'idkhuvucquanly' => 1,
                    'capkhuvucquanly' => 'QUOC_GIA',
                    'iddonvi' => 1,
                    'madonvi' => 'LDVN',
                    'tendonvi' => 'Lien doan',
                    'maloaidonvi' => 'LIEN_DOAN_BONG_CHUYEN_VN',
                    'can_higher_eligibility' => true,
                    'can_approve_coach_accounts' => true,
                ],
            ], $overrides),
        ];
    }

    public function test_organizer_venue_routes_exist(): void
    {
        $paths = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        foreach ([
            'ban-to-chuc',
            'ban-to-chuc/san-dau',
            'api/organizer/competition-locations',
            'api/organizer/venues',
            'api/organizer/venues/{venueId}',
            'api/organizer/venues/{venueId}/update',
            'api/organizer/venues/{venueId}/deactivate',
            'api/organizer/venues/{venueId}/remove',
            'api/organizer/venues/{venueId}/delete',
        ] as $path) {
            $this->assertContains($path, $paths);
        }
    }

    public function test_organizer_web_routes_require_login(): void
    {
        $this->get('/ban-to-chuc/san-dau')->assertRedirect('/login');
    }

    public function test_organizer_api_routes_require_login(): void
    {
        $this->getJson('/api/organizer/venues')->assertStatus(401)->assertJson([
            'success' => false,
        ]);
    }

    public function test_organizer_web_routes_reject_non_organizer_role(): void
    {
        $this->withSession($this->organizerSession(['role' => 'HUAN_LUYEN_VIEN']))
            ->get('/ban-to-chuc/san-dau')
            ->assertStatus(403);
    }

    public function test_organizer_pages_render_for_organizer_session(): void
    {
        foreach ([
            '/ban-to-chuc' => 'Tong quan ban to chuc',
            '/ban-to-chuc/san-dau' => 'Quan ly san dau',
        ] as $path => $text) {
            $this->withSession($this->organizerSession())
                ->get($path)
                ->assertOk()
                ->assertSee($text);
        }
    }

    public function test_admin_can_open_organizer_dashboard_only(): void
    {
        $this->withSession($this->organizerSession(['role' => 'ADMIN']))
            ->get('/ban-to-chuc')
            ->assertOk()
            ->assertSee('Tong quan ban to chuc');

        $this->withSession($this->organizerSession(['role' => 'ADMIN']))
            ->get('/ban-to-chuc/san-dau')
            ->assertStatus(403);
    }
}
