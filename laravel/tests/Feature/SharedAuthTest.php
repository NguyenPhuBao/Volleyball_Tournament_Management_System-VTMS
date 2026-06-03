<?php

namespace Tests\Feature;

use Tests\TestCase;

final class SharedAuthTest extends TestCase
{
    public function test_login_page_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Dang nhap');
    }

    public function test_api_login_requires_credentials(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Vui long nhap ten dang nhap va mat khau.',
                'user' => null,
            ]);
    }

    public function test_dashboard_redirects_guest_to_login(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_api_me_requires_login(): void
    {
        $this->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Vui long dang nhap.',
            ]);
    }
}
