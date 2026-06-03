# VTMS Laravel Registration Phase 3 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port the public coach/referee registration flow from pure PHP into `laravel/` while preserving existing route aliases and response shapes.

**Architecture:** Add Laravel public registration controllers, services, repositories, Blade pages, and routes. Keep the legacy project untouched; mirror the existing service validation and DB transaction behavior with Laravel DB repositories.

**Tech Stack:** PHP 8.3, Laravel 12, Laravel feature tests, MySQL via Laravel DB facade, Blade.

---

## File Structure

- Create: `laravel/tests/Feature/PublicRegistrationRouteCompatibilityTest.php`
  - Verifies all public registration route aliases exist, pages render, and invalid API payloads return legacy `422` JSON.
- Create: `laravel/app/Http/Controllers/Public/CoachRegistrationController.php`
  - Handles `/huanluyenvien/dang-ky`, `/api/coach/register/options`, and coach registration POST aliases.
- Create: `laravel/app/Http/Controllers/Public/RefereeRegistrationController.php`
  - Handles referee registration pages/options/POST aliases.
- Create: `laravel/app/Repositories/Public/CoachRegistrationRepository.php`
  - Encapsulates `Taikhoan`, `Nguoidung`, `Huanluyenvien`, `YeuCauXacNhan`, `Nhatkyhethong`, and `Khuvuc` reads/writes for coach registration.
- Create: `laravel/app/Repositories/Public/RefereeRegistrationRepository.php`
  - Encapsulates `Taikhoan`, `Nguoidung`, `Trongtai`, `YeuCauXacNhan`, `Nhatkyhethong`, and referee level reads/writes.
- Create: `laravel/app/Services/Public/CoachRegistrationService.php`
  - Ports legacy coach validation, role lookup, national federation organizer guard, transaction, and response shape.
- Create: `laravel/app/Services/Public/RefereeRegistrationService.php`
  - Ports legacy referee validation, level lookup, role lookup, national federation organizer guard, transaction, and response shape.
- Create: `laravel/resources/views/public/coach-register.blade.php`
- Create: `laravel/resources/views/public/referee-register.blade.php`
- Modify: `laravel/routes/web.php`
  - Add public coach/referee registration pages.
- Modify: `laravel/routes/api.php`
  - Add public registration options and POST aliases.

---

### Task 1: Public Registration Route Tests

**Files:**
- Create: `laravel/tests/Feature/PublicRegistrationRouteCompatibilityTest.php`

- [ ] **Step 1: Write failing route/page/API validation tests**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run from `laravel/`:

```bash
../runtime/php-8.3/php.exe artisan test --filter=PublicRegistrationRouteCompatibilityTest
```

Expected: FAIL because the public registration routes do not exist yet.

- [ ] **Step 3: Commit failing tests**

```bash
git add laravel/tests/Feature/PublicRegistrationRouteCompatibilityTest.php
git commit -m "test: add Laravel public registration route tests"
```

---

### Task 2: Coach Registration Port

**Files:**
- Create: `laravel/app/Http/Controllers/Public/CoachRegistrationController.php`
- Create: `laravel/app/Repositories/Public/CoachRegistrationRepository.php`
- Create: `laravel/app/Services/Public/CoachRegistrationService.php`
- Create: `laravel/resources/views/public/coach-register.blade.php`
- Modify: `laravel/routes/web.php`
- Modify: `laravel/routes/api.php`

- [ ] **Step 1: Implement coach repository**

Port these legacy methods from `app/backend/models/huanluyenvien-dulieu.php`:

- `activeWorkRegions()`
- `activeWorkRegion(int $regionId)`
- `roleIdByName(string $roleName)`
- `nationalFederationOrganizer()`
- `accountValueExists(string $field, string $value)`
- `profileValueExists(string $field, string $value)`
- `registerAccount(array $account, array $profile, array $coach, array $confirmation, ?string $ipAddress, string $logNote)`
- `findForOrganizer(int $organizerId, int $coachId)`

- [ ] **Step 2: Implement coach service**

Port `CoachRegistrationService::options()` and `register()` behavior exactly:

- Empty payload returns `422` with legacy error keys.
- Duplicate account/profile checks use repository methods.
- Missing `HUAN_LUYEN_VIEN` role returns `500`.
- Missing national federation organizer returns `409`.
- Successful registration returns `201`, `registration`, and `data` coach payload.

- [ ] **Step 3: Implement coach controller/view/routes**

Routes:

```php
Route::get('/huanluyenvien/dang-ky', [CoachRegistrationController::class, 'page']);
Route::get('/coach/register', [CoachRegistrationController::class, 'page']);
```

API aliases:

```php
Route::get('/coach/register/options', [CoachRegistrationController::class, 'options']);
Route::post('/auth/register/coach', [CoachRegistrationController::class, 'store']);
Route::post('/register/coach', [CoachRegistrationController::class, 'store']);
Route::post('/coach/register', [CoachRegistrationController::class, 'store']);
Route::post('/coaches/register', [CoachRegistrationController::class, 'store']);
Route::post('/huan-luyen-vien/register', [CoachRegistrationController::class, 'store']);
Route::post('/huanluyenvien/register', [CoachRegistrationController::class, 'store']);
```

- [ ] **Step 4: Run focused tests**

```bash
../runtime/php-8.3/php.exe artisan test --filter=PublicRegistrationRouteCompatibilityTest
```

Expected: Coach tests pass; referee tests still fail until Task 3 is complete.

---

### Task 3: Referee Registration Port

**Files:**
- Create: `laravel/app/Http/Controllers/Public/RefereeRegistrationController.php`
- Create: `laravel/app/Repositories/Public/RefereeRegistrationRepository.php`
- Create: `laravel/app/Services/Public/RefereeRegistrationService.php`
- Create: `laravel/resources/views/public/referee-register.blade.php`
- Modify: `laravel/routes/web.php`
- Modify: `laravel/routes/api.php`

- [ ] **Step 1: Implement referee repository**

Port these legacy methods from `app/backend/models/trongtai-dulieu.php`:

- `activeRefereeLevels()`
- `activeRefereeLevel(string $level)`
- `roleIdByName(string $roleName)`
- `nationalFederationOrganizer()`
- `accountValueExists(string $field, string $value)`
- `profileValueExists(string $field, string $value)`
- `registerAccount(array $account, array $profile, array $referee, array $confirmation, ?string $ipAddress, string $logNote)`
- `findById(int $refereeId)`

- [ ] **Step 2: Implement referee service**

Port `RefereeRegistrationService::options()` and `register()` behavior exactly:

- Empty payload returns `422` with legacy error keys including `capbac`.
- Invalid or inactive `capbac` returns legacy validation error.
- Missing `TRONG_TAI` role returns `500`.
- Missing national federation organizer returns `409`.
- Successful registration returns `201`, `registration`, and `data` referee payload.

- [ ] **Step 3: Implement referee controller/view/routes**

Routes:

```php
Route::get('/referee/register', [RefereeRegistrationController::class, 'page']);
Route::get('/trong-tai/dang-ky', [RefereeRegistrationController::class, 'page']);
Route::get('/trongtai/dang-ky', [RefereeRegistrationController::class, 'page']);
```

API aliases:

```php
Route::get('/referee/register/options', [RefereeRegistrationController::class, 'options']);
Route::post('/auth/register/referee', [RefereeRegistrationController::class, 'store']);
Route::post('/register/referee', [RefereeRegistrationController::class, 'store']);
Route::post('/referee/register', [RefereeRegistrationController::class, 'store']);
Route::post('/referees/register', [RefereeRegistrationController::class, 'store']);
Route::post('/trong-tai/register', [RefereeRegistrationController::class, 'store']);
Route::post('/trongtai/register', [RefereeRegistrationController::class, 'store']);
```

- [ ] **Step 4: Run focused tests**

```bash
../runtime/php-8.3/php.exe artisan test --filter=PublicRegistrationRouteCompatibilityTest
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
../runtime/php-8.3/php.exe artisan route:list --path=register
```

Expected: Coach/referee page and API aliases are listed.

- [ ] **Step 3: Commit implementation**

```bash
git add laravel/app/Http/Controllers/Public laravel/app/Repositories/Public laravel/app/Services/Public laravel/resources/views/public laravel/routes/web.php laravel/routes/api.php
git commit -m "feat: port public registration routes to Laravel"
```
