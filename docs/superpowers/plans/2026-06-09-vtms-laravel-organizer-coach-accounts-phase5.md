# VTMS Laravel Organizer Coach Accounts Phase 5 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port the Ban to chuc "Tai khoan HLV" approval module into `laravel/` while preserving legacy route aliases, guard behavior, response shape, account status transitions, and audit logs.

**Architecture:** Add a focused organizer coach-account controller, service, repository, Blade page, and route set. Keep the legacy project untouched. The page uses the organizer permission already stored in the Laravel legacy session, while API actions use the authoritative organizer DB guard before listing, showing, approving, or rejecting accounts.

**Tech Stack:** PHP 8.3, Laravel 12, Laravel feature tests, MySQL via Laravel DB facade, Blade.

---

## File Structure

- Create: `laravel/tests/Feature/OrganizerCoachAccountRouteCompatibilityTest.php`
  - Verifies route aliases exist, auth/role guards match legacy behavior, and permitted organizer sessions can render the page.
- Create: `laravel/app/Http/Controllers/Organizer/OrganizerCoachAccountController.php`
  - Handles `/ban-to-chuc/tai-khoan-hlv` and coach account API endpoints.
- Create: `laravel/app/Repositories/Organizer/OrganizerCoachAccountRepository.php`
  - Encapsulates `Taikhoan`, `Role`, `Nguoidung`, `Nhatkyhethong`, and `Nhatkytrangthai` reads/writes for coach account approval.
- Create: `laravel/app/Services/Organizer/OrganizerCoachAccountService.php`
  - Ports legacy national federation organizer guard, list/show, approve, and reject behavior.
- Create: `laravel/resources/views/organizer/coach-accounts.blade.php`
- Modify: `laravel/routes/web.php`
  - Add organizer coach account page route.
- Modify: `laravel/routes/api.php`
  - Add organizer coach account API aliases.

---

### Task 1: Organizer Coach Account Route Tests

- [ ] **Step 1: Write failing route/page/API guard tests**

Cover these legacy paths:

- `ban-to-chuc/tai-khoan-hlv`
- `api/organizer/coach-accounts`
- `api/organizer/coach-accounts/{accountId}`
- `api/organizer/coach-accounts/{accountId}/approve`
- `api/organizer/coach-accounts/{accountId}/reject`

- [ ] **Step 2: Run focused test to verify RED**

Run from `laravel/`:

```bash
../runtime/php-8.3/php.exe artisan test --filter=OrganizerCoachAccountRouteCompatibilityTest
```

Expected: FAIL because organizer coach account routes are not ported yet.

- [ ] **Step 3: Commit failing tests**

```bash
git add laravel/tests/Feature/OrganizerCoachAccountRouteCompatibilityTest.php
git commit -m "test: add Laravel organizer coach account route tests"
```

---

### Task 2: Organizer Coach Account Port

- [ ] **Step 1: Implement repository**

Port these legacy account operations:

- `Taikhoan::listAccounts()` for role `HUAN_LUYEN_VIEN`
- `Taikhoan::findById()`
- `Taikhoan::updateAccount()` for `trangthai`
- `Taikhoan::recordStatusHistory()`
- `Taikhoan::recordSystemLog()`

- [ ] **Step 2: Implement service**

Port behavior from `OrganizerCoachAccountService`:

- Only active national federation organizer accounts can list/show/approve/reject.
- `all()` always filters role to `HUAN_LUYEN_VIEN`.
- `find()` returns `404` when the account is missing or not a coach account.
- `approve()` only accepts `CHO_DUYET`, changes status to `HOAT_DONG`, records status history and system log.
- `reject()` only accepts `CHO_DUYET`, changes status to `DA_HUY`, records status history and system log.

- [ ] **Step 3: Implement controller/view/routes**

Web route:

```php
Route::get('/ban-to-chuc/tai-khoan-hlv', [OrganizerCoachAccountController::class, 'page']);
```

API routes:

```php
Route::get('/organizer/coach-accounts', [OrganizerCoachAccountController::class, 'index']);
Route::get('/organizer/coach-accounts/{accountId}', [OrganizerCoachAccountController::class, 'show']);
Route::post('/organizer/coach-accounts/{accountId}/approve', [OrganizerCoachAccountController::class, 'approve']);
Route::post('/organizer/coach-accounts/{accountId}/reject', [OrganizerCoachAccountController::class, 'reject']);
```

- [ ] **Step 4: Run focused tests**

```bash
../runtime/php-8.3/php.exe artisan test --filter=OrganizerCoachAccountRouteCompatibilityTest
```

Expected: PASS.

---

### Task 3: Full Verification and Commit

- [ ] **Step 1: Run full Laravel tests**

```bash
../runtime/php-8.3/php.exe artisan test
```

- [ ] **Step 2: Inspect organizer coach account routes**

```bash
../runtime/php-8.3/php.exe artisan route:list --path=tai-khoan-hlv
../runtime/php-8.3/php.exe artisan route:list --path=organizer/coach-accounts
```

- [ ] **Step 3: Commit implementation**

```bash
git add laravel/app/Http/Controllers/Organizer/OrganizerCoachAccountController.php laravel/app/Repositories/Organizer/OrganizerCoachAccountRepository.php laravel/app/Services/Organizer/OrganizerCoachAccountService.php laravel/resources/views/organizer/coach-accounts.blade.php laravel/routes/web.php laravel/routes/api.php
git commit -m "feat: port organizer coach account routes to Laravel"
```
