<?php

namespace Tests\Unit;

use App\Services\Shared\AuditLogService;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class AuditLogServiceTest extends TestCase
{
    public function test_describes_publish_tournament_request(): void
    {
        $request = Request::create('/api/organizer/tournaments/42/publish', 'POST');

        $entry = (new AuditLogService())->describe($request, '/api/organizer/tournaments/{id}/publish', ['id' => 42], [
            'id' => 7,
            'role' => 'ADMIN',
        ]);

        $this->assertSame('Duyet / xac nhan du lieu', $entry['action']);
        $this->assertSame('Giaidau', $entry['target_table']);
        $this->assertSame(42, $entry['target_id']);
        $this->assertStringContainsString('Tai khoan #7 ADMIN', $entry['note']);
        $this->assertStringContainsString('POST /api/organizer/tournaments/42/publish', $entry['note']);
    }

    public function test_describes_guest_login_request_without_leaking_password(): void
    {
        $request = Request::create('/api/auth/login', 'POST', [
            'username' => 'admin_test',
            'password' => 'secret',
        ]);

        $entry = (new AuditLogService())->describe($request, '/api/auth/login', [], null);

        $this->assertSame('Dang nhap', $entry['action']);
        $this->assertSame('Nhatkyhethong', $entry['target_table']);
        $this->assertNull($entry['target_id']);
        $this->assertStringContainsString('Khach chua dang nhap', $entry['note']);
        $this->assertStringNotContainsString('secret', $entry['note']);
        $this->assertStringContainsString('[REDACTED]', $entry['note']);
    }
}
