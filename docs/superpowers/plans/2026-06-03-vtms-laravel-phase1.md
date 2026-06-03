# VTMS Laravel Phase 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first runnable Laravel slice in `laravel/` while keeping the existing PHP MVC app intact.

**Architecture:** Create a Laravel 12 application beside the legacy app, then port shared bootstrap behavior: database config, session auth, role middleware, request audit logging, login/logout, home, dashboard, password page shell, and the first route compatibility tests. Business logic remains service/repository based and uses the current `vtms` schema through Laravel's DB facade.

**Tech Stack:** Laravel 12, PHP 8.3 portable for Windows, Composer PHAR, MySQL/MariaDB, PHPUnit/Pest feature tests, Blade, existing CSS/JS copied into `laravel/public/assets`.

---

## Scope Check

The approved design covers the whole VTMS migration, but that includes independent Admin, Organizer, Referee, Coach, and Athlete modules. This plan intentionally implements Phase 1 only. Later plans should port each role module with the same route/test/service pattern after this Laravel shell is runnable.

## File Structure

- Create: `runtime/php-8.3/` for workspace-local PHP 8.3.
- Create: `runtime/composer/composer.phar` for workspace-local Composer.
- Create: `laravel/` via `composer create-project`.
- Modify: `laravel/.env`, `laravel/.env.example`, `laravel/config/app.php`, `laravel/config/session.php`, `laravel/bootstrap/app.php`.
- Create: `laravel/app/Support/LegacySessionUser.php`.
- Create: `laravel/app/Repositories/AccountRepository.php`.
- Create: `laravel/app/Services/Shared/AuthService.php`.
- Create: `laravel/app/Services/Shared/AuditLogService.php`.
- Create: `laravel/app/Http/Middleware/LegacyAuthenticate.php`.
- Create: `laravel/app/Http/Middleware/RoleMiddleware.php`.
- Create: `laravel/app/Http/Middleware/AuditRequestMiddleware.php`.
- Create: `laravel/app/Http/Controllers/Shared/HomeController.php`.
- Create: `laravel/app/Http/Controllers/Shared/AuthController.php`.
- Create: `laravel/app/Http/Controllers/Shared/DashboardController.php`.
- Create: `laravel/app/Http/Controllers/Shared/AccountSecurityController.php`.
- Modify: `laravel/routes/web.php`, `laravel/routes/api.php`.
- Create: `laravel/resources/views/layouts/auth.blade.php`, `laravel/resources/views/layouts/main.blade.php`.
- Create: `laravel/resources/views/public/home.blade.php`, `laravel/resources/views/public/login.blade.php`.
- Create: `laravel/resources/views/dashboard/index.blade.php`.
- Create: `laravel/resources/views/account/change-password.blade.php`.
- Create: `laravel/resources/views/errors/403.blade.php`, `laravel/resources/views/errors/404.blade.php`, `laravel/resources/views/errors/500.blade.php`.
- Copy: `public/assets/**` to `laravel/public/assets/**`.
- Create: `laravel/tests/Feature/SharedAuthTest.php`.
- Create: `laravel/tests/Feature/RouteCompatibilityTest.php`.

### Task 1: Provision Local PHP And Scaffold Laravel

**Files:**
- Create: `runtime/php-8.3/`
- Create: `runtime/composer/composer.phar`
- Create: `laravel/`

- [ ] **Step 1: Confirm the current PHP cannot run Laravel 12**

Run:

```powershell
php -v
C:\xampp\php\php.exe -v
```

Expected: `php` is not in PATH, and XAMPP PHP reports `PHP 8.0.30`, which is below Laravel 12's PHP 8.2 minimum.

- [ ] **Step 2: Download workspace-local PHP 8.3**

Run:

```powershell
New-Item -ItemType Directory -Force runtime | Out-Null
Invoke-WebRequest -Uri "https://windows.php.net/downloads/releases/latest/php-8.3-Win32-vs16-x64-latest.zip" -OutFile "runtime/php-8.3.zip"
Expand-Archive -Path "runtime/php-8.3.zip" -DestinationPath "runtime/php-8.3" -Force
Copy-Item "runtime/php-8.3/php.ini-development" "runtime/php-8.3/php.ini" -Force
```

Expected: `runtime/php-8.3/php.exe` exists.

- [ ] **Step 3: Enable Laravel-required PHP extensions**

Run:

```powershell
$phpIni = "runtime/php-8.3/php.ini"
$ini = Get-Content -Raw $phpIni
$ini = $ini -replace ';extension_dir = "ext"', 'extension_dir = "ext"'
$ini = $ini -replace ';extension=curl', 'extension=curl'
$ini = $ini -replace ';extension=fileinfo', 'extension=fileinfo'
$ini = $ini -replace ';extension=mbstring', 'extension=mbstring'
$ini = $ini -replace ';extension=openssl', 'extension=openssl'
$ini = $ini -replace ';extension=pdo_mysql', 'extension=pdo_mysql'
$ini = $ini -replace ';extension=zip', 'extension=zip'
$ini = $ini -replace ';date.timezone =', 'date.timezone = Asia/Ho_Chi_Minh'
Set-Content -Path $phpIni -Value $ini
.\runtime\php-8.3\php.exe -v
.\runtime\php-8.3\php.exe -m | Select-String "curl|fileinfo|mbstring|openssl|PDO|pdo_mysql|zip"
```

Expected: PHP version is 8.3.x and listed modules include `curl`, `fileinfo`, `mbstring`, `openssl`, `PDO`, `pdo_mysql`, and `zip`.

- [ ] **Step 4: Download Composer PHAR locally**

Run:

```powershell
New-Item -ItemType Directory -Force runtime/composer | Out-Null
Invoke-WebRequest -Uri "https://getcomposer.org/download/latest-stable/composer.phar" -OutFile "runtime/composer/composer.phar"
.\runtime\php-8.3\php.exe .\runtime\composer\composer.phar --version
```

Expected: Composer prints a 2.x version.

- [ ] **Step 5: Create Laravel 12 project**

Run:

```powershell
.\runtime\php-8.3\php.exe .\runtime\composer\composer.phar create-project laravel/laravel:^12.0 laravel --no-interaction
```

Expected: `laravel/composer.json`, `laravel/artisan`, and `laravel/vendor/autoload.php` exist.

- [ ] **Step 6: Commit scaffold**

Run:

```powershell
git add laravel runtime/composer/composer.phar
git commit -m "chore: scaffold Laravel app"
```

Expected: Commit succeeds. Do not commit `runtime/php-8.3/` or `runtime/php-8.3.zip`; add ignore rules if needed before committing.

### Task 2: Add Phase 1 Failing Tests

**Files:**
- Create: `laravel/tests/Feature/SharedAuthTest.php`
- Create: `laravel/tests/Feature/RouteCompatibilityTest.php`

- [ ] **Step 1: Write shared auth feature tests**

Create `laravel/tests/Feature/SharedAuthTest.php`:

```php
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
```

- [ ] **Step 2: Write route compatibility smoke tests**

Create `laravel/tests/Feature/RouteCompatibilityTest.php`:

```php
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
```

- [ ] **Step 3: Run tests and confirm they fail**

Run:

```powershell
cd laravel
..\runtime\php-8.3\php.exe artisan test tests/Feature/SharedAuthTest.php tests/Feature/RouteCompatibilityTest.php
cd ..
```

Expected: FAIL because controllers, middleware, and compatibility routes are not implemented yet.

- [ ] **Step 4: Commit failing tests**

Run:

```powershell
git add laravel/tests/Feature/SharedAuthTest.php laravel/tests/Feature/RouteCompatibilityTest.php
git commit -m "test: add Laravel shared auth parity tests"
```

Expected: Commit succeeds with failing tests documented.

### Task 3: Configure Laravel Environment And Middleware

**Files:**
- Modify: `laravel/.env.example`
- Modify: `laravel/bootstrap/app.php`
- Create: `laravel/app/Support/LegacySessionUser.php`
- Create: `laravel/app/Http/Middleware/LegacyAuthenticate.php`
- Create: `laravel/app/Http/Middleware/RoleMiddleware.php`

- [ ] **Step 1: Configure environment defaults**

Update `laravel/.env.example` to include:

```env
APP_NAME=VTMS
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8012
APP_TIMEZONE=Asia/Ho_Chi_Minh

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vtms
DB_USERNAME=root
DB_PASSWORD=123456

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_COOKIE=VTMS_SESSION
```

- [ ] **Step 2: Add legacy session support class**

Create `laravel/app/Support/LegacySessionUser.php`:

```php
<?php

namespace App\Support;

final class LegacySessionUser
{
    private const USER_KEY = 'auth_user';
    private const TOKEN_KEY = 'auth_session_token';

    public static function check(): bool
    {
        return is_array(session(self::USER_KEY));
    }

    public static function user(): ?array
    {
        $user = session(self::USER_KEY);

        return is_array($user) ? $user : null;
    }

    public static function id(): int
    {
        return (int) (self::user()['id'] ?? 0);
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function hasRole(array $roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    public static function login(array $user, ?string $sessionToken = null): void
    {
        session()->regenerate();
        session([self::USER_KEY => $user]);

        if ($sessionToken !== null) {
            session([self::TOKEN_KEY => $sessionToken]);
        }
    }

    public static function logout(): void
    {
        session()->forget([self::USER_KEY, self::TOKEN_KEY]);
        session()->regenerate();
    }

    public static function sessionToken(): ?string
    {
        $token = session(self::TOKEN_KEY);

        return is_string($token) ? $token : null;
    }
}
```

- [ ] **Step 3: Add legacy auth middleware**

Create `laravel/app/Http/Middleware/LegacyAuthenticate.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Support\LegacySessionUser;
use Closure;
use Illuminate\Http\Request;

final class LegacyAuthenticate
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (LegacySessionUser::check()) {
            return $next($request);
        }

        if ($request->expectsJson() || str_starts_with('/' . ltrim($request->path(), '/'), '/api/')) {
            return response()->json([
                'success' => false,
                'message' => 'Vui long dang nhap.',
            ], 401);
        }

        return redirect('/login');
    }
}
```

- [ ] **Step 4: Add role middleware**

Create `laravel/app/Http/Middleware/RoleMiddleware.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Support\LegacySessionUser;
use Closure;
use Illuminate\Http\Request;

final class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $roles): mixed
    {
        $requiredRoles = array_map('trim', explode(',', $roles));

        if (!LegacySessionUser::check()) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Vui long dang nhap.'], 401)
                : redirect('/login');
        }

        if (!LegacySessionUser::hasRole($requiredRoles)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tai khoan khong co quyen thuc hien thao tac nay.',
                    'required_roles' => $requiredRoles,
                    'current_role' => LegacySessionUser::role(),
                ], 403);
            }

            return response()->view('errors.403', [
                'role' => LegacySessionUser::role(),
                'requiredRoles' => $requiredRoles,
            ], 403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 5: Register middleware aliases**

Modify `laravel/bootstrap/app.php` middleware section:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'legacy.auth' => \App\Http\Middleware\LegacyAuthenticate::class,
        'legacy.role' => \App\Http\Middleware\RoleMiddleware::class,
    ]);
})
```

- [ ] **Step 6: Run tests**

Run:

```powershell
cd laravel
..\runtime\php-8.3\php.exe artisan test tests/Feature/SharedAuthTest.php tests/Feature/RouteCompatibilityTest.php
cd ..
```

Expected: Some tests still fail because routes/controllers are not implemented yet; middleware class autoloading must not error.

- [ ] **Step 7: Commit middleware foundation**

Run:

```powershell
git add laravel/.env.example laravel/bootstrap/app.php laravel/app/Support laravel/app/Http/Middleware
git commit -m "feat: add Laravel legacy session middleware"
```

Expected: Commit succeeds.

### Task 4: Port Shared Auth Services And Routes

**Files:**
- Create: `laravel/app/Repositories/AccountRepository.php`
- Create: `laravel/app/Services/Shared/AuthService.php`
- Create: `laravel/app/Http/Controllers/Shared/AuthController.php`
- Modify: `laravel/routes/web.php`
- Modify: `laravel/routes/api.php`

- [ ] **Step 1: Implement account repository**

Create `laravel/app/Repositories/AccountRepository.php` with methods ported from `app/backend/models/taikhoan-dulieu.php`: `findByIdentifier`, `createLoginSession`, `closeLoginSession`, `recordLoginHistory`, and `findByIdWithPassword`. Use `DB::selectOne`, `DB::insert`, and `DB::update`; preserve table/column names exactly.

- [ ] **Step 2: Implement auth service**

Create `laravel/app/Services/Shared/AuthService.php` by porting `app/backend/services/Shared/xacthuc-dichvu.php`. Replace legacy `Auth::login/logout` calls with `LegacySessionUser::login/logout`, and replace legacy request methods with Laravel `Request::ip()` and `Request::userAgent()`.

- [ ] **Step 3: Implement auth controller**

Create `laravel/app/Http/Controllers/Shared/AuthController.php`:

```php
<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Services\Shared\AuthService;
use App\Support\LegacySessionUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function showLogin(): Response|RedirectResponse
    {
        if (LegacySessionUser::check()) {
            return redirect('/dashboard');
        }

        return response()->view('public.login', [
            'error' => session('login_error'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $result = $this->auth->attempt(
            trim((string) ($request->input('username') ?? $request->input('identifier', ''))),
            (string) $request->input('password', ''),
            $request
        );

        if ($result['ok']) {
            $request->session()->forget('login_error');
            return redirect('/dashboard');
        }

        return redirect('/login')->with('login_error', $result['message']);
    }

    public function logout(): RedirectResponse
    {
        $this->auth->logout();

        return redirect('/');
    }

    public function apiLogin(Request $request): \Illuminate\Http\JsonResponse
    {
        $result = $this->auth->attempt(
            trim((string) ($request->input('username') ?? $request->input('identifier', ''))),
            (string) $request->input('password', ''),
            $request
        );

        return response()->json([
            'success' => $result['ok'],
            'message' => $result['message'],
            'user' => $result['user'],
        ], (int) $result['status']);
    }

    public function apiLogout(): \Illuminate\Http\JsonResponse
    {
        $this->auth->logout();

        return response()->json([
            'success' => true,
            'message' => 'Dang xuat thanh cong.',
        ]);
    }

    public function apiMe(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => LegacySessionUser::user(),
        ]);
    }
}
```

- [ ] **Step 4: Add shared routes**

Update `laravel/routes/web.php`:

```php
<?php

use App\Http\Controllers\Shared\AuthController;
use App\Http\Controllers\Shared\DashboardController;
use App\Http\Controllers\Shared\HomeController;
use App\Http\Controllers\Shared\AccountSecurityController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::get('/login', [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('legacy.auth');
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('legacy.auth');
Route::get('/tai-khoan/doi-mat-khau', [AccountSecurityController::class, 'page'])->middleware('legacy.auth');
Route::get('/account/change-password', [AccountSecurityController::class, 'page'])->middleware('legacy.auth');
```

Update `laravel/routes/api.php`:

```php
<?php

use App\Http\Controllers\Shared\AccountSecurityController;
use App\Http\Controllers\Shared\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'apiLogin']);
Route::post('/auth/logout', [AuthController::class, 'apiLogout'])->middleware('legacy.auth');
Route::get('/auth/me', [AuthController::class, 'apiMe'])->middleware('legacy.auth');
Route::post('/account/password', [AccountSecurityController::class, 'changePassword'])->middleware('legacy.auth');
Route::post('/auth/change-password', [AccountSecurityController::class, 'changePassword'])->middleware('legacy.auth');
```

- [ ] **Step 5: Run tests**

Run:

```powershell
cd laravel
..\runtime\php-8.3\php.exe artisan test tests/Feature/SharedAuthTest.php tests/Feature/RouteCompatibilityTest.php
cd ..
```

Expected: Auth route tests pass except pages that still need Blade controllers/views.

- [ ] **Step 6: Commit auth routes**

Run:

```powershell
git add laravel/app/Repositories laravel/app/Services laravel/app/Http/Controllers/Shared/AuthController.php laravel/routes
git commit -m "feat: port shared auth routes to Laravel"
```

Expected: Commit succeeds.

### Task 5: Add Shared Pages, Assets, And Audit Logging

**Files:**
- Create: shared controllers and Blade views listed in File Structure.
- Create: `laravel/app/Services/Shared/AuditLogService.php`
- Create: `laravel/app/Http/Middleware/AuditRequestMiddleware.php`
- Modify: `laravel/bootstrap/app.php`
- Copy: `public/assets/**` to `laravel/public/assets/**`

- [ ] **Step 1: Copy assets**

Run:

```powershell
New-Item -ItemType Directory -Force laravel/public/assets | Out-Null
Copy-Item public/assets/* laravel/public/assets/ -Recurse -Force
```

Expected: Laravel has the same CSS/JS public assets as the current app.

- [ ] **Step 2: Port minimal shared controllers**

Create `HomeController`, `DashboardController`, and `AccountSecurityController` under `laravel/app/Http/Controllers/Shared`. Each controller returns the matching Blade view and preserves route behavior: home shows public page, dashboard requires session user, account page requires session user, password API returns the same success/error JSON shape.

- [ ] **Step 3: Port shared Blade views**

Create Blade layouts and pages by converting the existing PHP views:

- `app/frontend/layout/bocuc-xacthuc.php` -> `laravel/resources/views/layouts/auth.blade.php`
- `app/frontend/layout/bocuc-chinh.php` -> `laravel/resources/views/layouts/main.blade.php`
- `app/frontend/views/public/congkhai-trangchu.php` -> `laravel/resources/views/public/home.blade.php`
- `app/frontend/views/public/congkhai-dangnhap.php` -> `laravel/resources/views/public/login.blade.php`
- `app/frontend/views/dashboard/bangdieukhien-trangchu.php` -> `laravel/resources/views/dashboard/index.blade.php`
- `app/frontend/views/account/taikhoan-doimatkhau.php` -> `laravel/resources/views/account/change-password.blade.php`
- existing error views -> `laravel/resources/views/errors/*.blade.php`

Use these conversions consistently:

```blade
{{ $value }}
{!! csrf_field() !!}
{{ url('/path') }}
{{ asset('assets/css/file.css') }}
@extends('layouts.main')
@section('content')
@endsection
```

- [ ] **Step 4: Add audit middleware**

Create `AuditLogService` and `AuditRequestMiddleware` by porting the audit action/target/note logic from `app/backend/core/route/dinhtuyen-hethong.php`. Register it as web/API middleware in `laravel/bootstrap/app.php` after auth/session middleware.

- [ ] **Step 5: Run tests and route list**

Run:

```powershell
cd laravel
..\runtime\php-8.3\php.exe artisan test tests/Feature/SharedAuthTest.php tests/Feature/RouteCompatibilityTest.php
..\runtime\php-8.3\php.exe artisan route:list --path=auth
..\runtime\php-8.3\php.exe artisan route:list --path=dashboard
cd ..
```

Expected: Phase 1 tests pass; route list shows shared auth and dashboard routes.

- [ ] **Step 6: Commit shared pages and audit**

Run:

```powershell
git add laravel/app/Http/Controllers/Shared laravel/app/Http/Middleware/AuditRequestMiddleware.php laravel/app/Services/Shared/AuditLogService.php laravel/bootstrap/app.php laravel/resources/views laravel/public/assets
git commit -m "feat: port shared pages and audit logging"
```

Expected: Commit succeeds.

### Task 6: Final Phase 1 Verification

**Files:**
- Modify: `README.md` if local Laravel run instructions are added.

- [ ] **Step 1: Run full Laravel test suite**

Run:

```powershell
cd laravel
..\runtime\php-8.3\php.exe artisan test
cd ..
```

Expected: PASS.

- [ ] **Step 2: Start Laravel dev server**

Run:

```powershell
Start-Process -FilePath "$PWD\runtime\php-8.3\php.exe" `
  -ArgumentList @("artisan","serve","--host=127.0.0.1","--port=8012") `
  -WorkingDirectory "$PWD\laravel" `
  -WindowStyle Hidden
```

Expected: Laravel serves at `http://127.0.0.1:8012`.

- [ ] **Step 3: Smoke test endpoints**

Run:

```powershell
Invoke-WebRequest http://127.0.0.1:8012/login -UseBasicParsing | Select-Object -ExpandProperty StatusCode
Invoke-WebRequest http://127.0.0.1:8012/api/auth/me -UseBasicParsing -SkipHttpErrorCheck | Select-Object -ExpandProperty StatusCode
```

Expected: `/login` returns `200`; `/api/auth/me` returns `401`.

- [ ] **Step 4: Commit docs if updated**

Run:

```powershell
git status --short
git add README.md
git commit -m "docs: add Laravel phase 1 run instructions"
```

Expected: Commit only if README changed; otherwise skip.

## Self-Review

- Spec coverage: This plan covers the approved first two migration steps: `laravel/` scaffold and shared/auth/dashboard bootstrap. It intentionally defers Admin, Organizer, Referee, Coach, and Athlete modules to later phase plans.
- Placeholder scan: No `TBD`, `TODO`, or unspecified code placeholders are used.
- Type consistency: Controller, service, middleware, and support class names match the route and file structure sections.
