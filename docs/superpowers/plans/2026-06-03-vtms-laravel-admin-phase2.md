# VTMS Laravel Admin Phase 2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port the VTMS Admin module route surface from pure PHP into `laravel/` while keeping the legacy project runnable.

**Architecture:** Add Laravel Admin controllers, services, repositories, Blade pages, and route declarations that mirror the current legacy Admin URLs. Keep session and role checks on the existing shared middleware, then port JSON API behavior with query-builder repositories that preserve current table names and response shapes.

**Tech Stack:** PHP 8.3, Laravel 12, PHPUnit/Pest-compatible Laravel feature tests, MySQL via Laravel DB query builder, Blade views.

---

## File Structure

- Create: `laravel/tests/Feature/AdminRouteCompatibilityTest.php`
  - Verifies Admin route registration, guest redirects/401, role rejection, and Admin page rendering.
- Create: `laravel/app/Http/Controllers/Admin/AdminDashboardController.php`
  - Renders the Admin dashboard entry at `/admin`.
- Create: `laravel/app/Http/Controllers/Admin/AdminAccountController.php`
  - Renders account management page and handles `/api/admin/accounts*`.
- Create: `laravel/app/Http/Controllers/Admin/AdminUserController.php`
  - Renders user profile management page and handles `/api/admin/users*`.
- Create: `laravel/app/Http/Controllers/Admin/AdminSystemLogController.php`
  - Renders log page and handles `/api/admin/system-logs*`.
- Create: `laravel/app/Http/Controllers/Admin/AdminOrganizerChangeRequestController.php`
  - Renders organizer change confirmation page and handles `/api/admin/organizer-change-requests*`.
- Create: `laravel/app/Repositories/Admin/AdminAccountRepository.php`
  - Encapsulates `Taikhoan`, `Vaitro`, and `Nguoidung` reads/writes.
- Create: `laravel/app/Repositories/Admin/AdminUserRepository.php`
  - Encapsulates `Nguoidung` profile reads/writes.
- Create: `laravel/app/Repositories/Admin/AdminSystemLogRepository.php`
  - Encapsulates `Nhatkyhethong` reads and filter options.
- Create: `laravel/app/Repositories/Admin/AdminOrganizerChangeRequestRepository.php`
  - Encapsulates `Yeucaucapnhathoso`, `Nguoidung`, and approval/rejection updates.
- Create: `laravel/app/Services/Admin/AdminAccountService.php`
  - Preserves legacy account validation and password hashing behavior.
- Create: `laravel/app/Services/Admin/AdminUserService.php`
  - Preserves legacy profile update validation and response shape.
- Create: `laravel/app/Services/Admin/AdminSystemLogService.php`
  - Preserves legacy log list/detail/options response shape.
- Create: `laravel/app/Services/Admin/AdminOrganizerChangeRequestService.php`
  - Preserves legacy approval/rejection workflow.
- Create: `laravel/resources/views/admin/dashboard.blade.php`
- Create: `laravel/resources/views/admin/accounts.blade.php`
- Create: `laravel/resources/views/admin/user-profiles.blade.php`
- Create: `laravel/resources/views/admin/system-logs.blade.php`
- Create: `laravel/resources/views/admin/organizer-change-requests.blade.php`
- Modify: `laravel/routes/web.php`
  - Add Admin web routes under `legacy.auth` and `legacy.role:ADMIN`.
- Modify: `laravel/routes/api.php`
  - Add Admin API routes under `legacy.auth` and `legacy.role:ADMIN`.

---

### Task 1: Admin Route Parity Tests

**Files:**
- Create: `laravel/tests/Feature/AdminRouteCompatibilityTest.php`

- [ ] **Step 1: Write the failing route/auth test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run from `laravel/`:

```bash
../runtime/php-8.3/php.exe artisan test --filter=AdminRouteCompatibilityTest
```

Expected: FAIL because Admin routes and controllers do not exist yet.

- [ ] **Step 3: Commit failing tests**

```bash
git add laravel/tests/Feature/AdminRouteCompatibilityTest.php
git commit -m "test: add Laravel admin route parity tests"
```

---

### Task 2: Admin Web Pages

**Files:**
- Create controllers under `laravel/app/Http/Controllers/Admin/`
- Create Blade views under `laravel/resources/views/admin/`
- Modify: `laravel/routes/web.php`

- [ ] **Step 1: Implement Admin web controllers**

Each controller returns a Blade view with `pageTitle`, `moduleTitle`, and `user` from `LegacySessionUser::user()`.

- [ ] **Step 2: Implement Admin Blade pages**

Each page extends `layouts.main`, keeps legacy URL anchors/data attributes where practical, and includes visible headings matching the tests:

- `Tong quan quan tri`
- `Quan tri tai khoan`
- `Ho so nguoi dung`
- `Nhat ky he thong`
- `Xac nhan thong tin ban to chuc`

- [ ] **Step 3: Register Admin web routes**

Add:

```php
Route::middleware(['legacy.auth', 'legacy.role:ADMIN'])->group(function (): void {
    Route::get('/admin', [AdminDashboardController::class, 'page']);
    Route::get('/admin/users', [AdminAccountController::class, 'page']);
    Route::get('/admin/nguoi-dung', [AdminUserController::class, 'page']);
    Route::get('/admin/logs', [AdminSystemLogController::class, 'page']);
    Route::get('/admin/xac-nhan-thong-tin-btc', [AdminOrganizerChangeRequestController::class, 'page']);
});
```

- [ ] **Step 4: Run tests**

```bash
../runtime/php-8.3/php.exe artisan test --filter=AdminRouteCompatibilityTest
```

Expected: Remaining failures only for Admin API routes, if API routes are not registered yet.

---

### Task 3: Admin API Services and Routes

**Files:**
- Create repositories under `laravel/app/Repositories/Admin/`
- Create services under `laravel/app/Services/Admin/`
- Create API controller methods under `laravel/app/Http/Controllers/Admin/`
- Modify: `laravel/routes/api.php`

- [ ] **Step 1: Port account APIs**

Routes:

```php
Route::get('/admin/roles', [AdminAccountController::class, 'roles']);
Route::get('/admin/accounts', [AdminAccountController::class, 'index']);
Route::post('/admin/accounts', [AdminAccountController::class, 'store']);
Route::get('/admin/accounts/{id}', [AdminAccountController::class, 'show'])->whereNumber('id');
Route::match(['put', 'patch'], '/admin/accounts/{id}', [AdminAccountController::class, 'update'])->whereNumber('id');
Route::post('/admin/accounts/{id}/update', [AdminAccountController::class, 'update'])->whereNumber('id');
Route::delete('/admin/accounts/{id}', [AdminAccountController::class, 'destroy'])->whereNumber('id');
Route::post('/admin/accounts/{id}/delete', [AdminAccountController::class, 'destroy'])->whereNumber('id');
```

Preserve legacy responses with `success`, `message`, and data payload keys.

- [ ] **Step 2: Port user profile APIs**

Routes:

```php
Route::get('/admin/users', [AdminUserController::class, 'index']);
Route::get('/admin/users/{id}', [AdminUserController::class, 'show'])->whereNumber('id');
Route::match(['put', 'patch'], '/admin/users/{id}', [AdminUserController::class, 'update'])->whereNumber('id');
Route::post('/admin/users/{id}/update', [AdminUserController::class, 'update'])->whereNumber('id');
```

Preserve legacy profile fields and update validation.

- [ ] **Step 3: Port system log APIs**

Routes:

```php
Route::get('/admin/system-logs', [AdminSystemLogController::class, 'index']);
Route::get('/admin/system-logs/options', [AdminSystemLogController::class, 'options']);
Route::get('/admin/system-logs/{id}', [AdminSystemLogController::class, 'show'])->whereNumber('id');
```

Preserve legacy filter names and pagination metadata.

- [ ] **Step 4: Port organizer change request APIs**

Routes:

```php
Route::get('/admin/organizer-change-requests', [AdminOrganizerChangeRequestController::class, 'index']);
Route::get('/admin/organizer-change-requests/{id}', [AdminOrganizerChangeRequestController::class, 'show'])->whereNumber('id');
Route::post('/admin/organizer-change-requests/{id}/approve', [AdminOrganizerChangeRequestController::class, 'approve'])->whereNumber('id');
Route::post('/admin/organizer-change-requests/{id}/reject', [AdminOrganizerChangeRequestController::class, 'reject'])->whereNumber('id');
```

Approval updates the linked user profile and marks request status as approved. Rejection marks request status as rejected with a reason when provided.

- [ ] **Step 5: Run focused Admin tests**

```bash
../runtime/php-8.3/php.exe artisan test --filter=AdminRouteCompatibilityTest
```

Expected: PASS.

---

### Task 4: Full Verification and Commit

**Files:**
- All files from Tasks 1-3.

- [ ] **Step 1: Run full Laravel tests**

```bash
../runtime/php-8.3/php.exe artisan test
```

Expected: All existing Laravel tests pass.

- [ ] **Step 2: Inspect route list**

```bash
../runtime/php-8.3/php.exe artisan route:list --path=admin
```

Expected: Admin web routes and Admin API routes are listed with `legacy.auth` and `legacy.role:ADMIN`.

- [ ] **Step 3: Commit implementation**

```bash
git add laravel/app/Http/Controllers/Admin laravel/app/Repositories/Admin laravel/app/Services/Admin laravel/resources/views/admin laravel/routes/web.php laravel/routes/api.php
git commit -m "feat: port admin routes to Laravel"
```
