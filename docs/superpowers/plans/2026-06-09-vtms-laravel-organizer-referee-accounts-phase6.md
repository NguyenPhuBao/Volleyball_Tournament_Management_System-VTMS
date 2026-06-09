# VTMS Laravel Organizer Referee Accounts Phase 6 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port the Ban to chuc "Tai khoan trong tai" approval module into `laravel/` while preserving legacy route aliases, guard behavior, response shape, referee profile transitions, confirmation request updates, and audit logs.

**Architecture:** Add a focused organizer referee-account controller, service, repository, Blade page, and route set. Keep the legacy project untouched. Add the missing `can_approve_referee_accounts` session flag so real organizer logins can render the page with the same authority rule used by coach-account approval.

**Tech Stack:** PHP 8.3, Laravel 12, Laravel feature tests, MySQL via Laravel DB facade, Blade.

---

## File Structure

- Create: `laravel/tests/Feature/OrganizerRefereeAccountRouteCompatibilityTest.php`
  - Verifies route aliases exist, auth/role guards match legacy behavior, and permitted organizer sessions can render the page.
- Create: `laravel/app/Http/Controllers/Organizer/OrganizerRefereeAccountController.php`
  - Handles `/ban-to-chuc/tai-khoan-trong-tai` and referee account API endpoints.
- Create: `laravel/app/Repositories/Organizer/OrganizerRefereeAccountRepository.php`
  - Encapsulates `Taikhoan`, `Role`, `Nguoidung`, `Trongtai`, `Yeucauxacnhan`, `Nhatkyhethong`, and `Nhatkytrangthai` reads/writes.
- Create: `laravel/app/Services/Organizer/OrganizerRefereeAccountService.php`
  - Ports legacy national federation organizer guard, list/show, approve, and reject behavior.
- Create: `laravel/resources/views/organizer/referee-accounts.blade.php`
- Modify: `laravel/app/Services/Shared/AuthService.php`
  - Adds `can_approve_referee_accounts` to organizer session context.
- Modify: `laravel/routes/web.php`
  - Add organizer referee account page route.
- Modify: `laravel/routes/api.php`
  - Add organizer referee account API aliases.

---

### Task 1: Organizer Referee Account Route Tests

- [ ] **Step 1: Write failing route/page/API guard tests**

Cover these legacy paths:

- `ban-to-chuc/tai-khoan-trong-tai`
- `api/organizer/referee-accounts`
- `api/organizer/referee-accounts/{accountId}`
- `api/organizer/referee-accounts/{accountId}/approve`
- `api/organizer/referee-accounts/{accountId}/reject`

- [ ] **Step 2: Run focused test to verify RED**

Run from `laravel/`:

```bash
../runtime/php-8.3/php.exe artisan test --filter=OrganizerRefereeAccountRouteCompatibilityTest
```

Expected: FAIL because organizer referee account routes are not ported yet.

- [ ] **Step 3: Commit failing tests**

```bash
git add laravel/tests/Feature/OrganizerRefereeAccountRouteCompatibilityTest.php
git commit -m "test: add Laravel organizer referee account route tests"
```

---

### Task 2: Organizer Referee Account Port

- [ ] **Step 1: Implement repository**

Port these legacy operations:

- List role `TRONG_TAI` accounts.
- Find role `TRONG_TAI` account by id.
- Find `Trongtai` profile by account id.
- Find latest `XAC_NHAN_TAI_KHOAN_TRONG_TAI` request for referee and organizer.
- Transactionally update `Trongtai`, `Taikhoan`, optional `Yeucauxacnhan`, status history, and system logs.

- [ ] **Step 2: Implement service**

Port behavior from `OrganizerRefereeAccountService`:

- Only active national federation organizer accounts can list/show/approve/reject.
- `all()` always filters role to `TRONG_TAI`.
- `find()` returns `404` when the account is missing or not a referee account, and attaches `referee` profile when available.
- `approve()` only accepts account and referee profile status `CHO_DUYET`, changes account to `HOAT_DONG`, referee to `HOAT_DONG`, confirmation request to `DA_DUYET` when pending, and records logs.
- `reject()` only accepts account and referee profile status `CHO_DUYET`, changes account to `DA_HUY`, referee to `NGUNG_HOAT_DONG`, confirmation request to `TU_CHOI` when pending, and records logs.

- [ ] **Step 3: Implement controller/view/routes/session flag**

Web route:

```php
Route::get('/ban-to-chuc/tai-khoan-trong-tai', [OrganizerRefereeAccountController::class, 'page']);
```

API routes:

```php
Route::get('/organizer/referee-accounts', [OrganizerRefereeAccountController::class, 'index']);
Route::get('/organizer/referee-accounts/{accountId}', [OrganizerRefereeAccountController::class, 'show']);
Route::post('/organizer/referee-accounts/{accountId}/approve', [OrganizerRefereeAccountController::class, 'approve']);
Route::post('/organizer/referee-accounts/{accountId}/reject', [OrganizerRefereeAccountController::class, 'reject']);
```

- [ ] **Step 4: Run focused tests**

```bash
../runtime/php-8.3/php.exe artisan test --filter=OrganizerRefereeAccountRouteCompatibilityTest
```

Expected: PASS.

---

### Task 3: Full Verification and Commit

- [ ] **Step 1: Run full Laravel tests**

```bash
../runtime/php-8.3/php.exe artisan test
```

- [ ] **Step 2: Inspect organizer referee account routes**

```bash
../runtime/php-8.3/php.exe artisan route:list --path=tai-khoan-trong-tai
../runtime/php-8.3/php.exe artisan route:list --path=organizer/referee-accounts
```

- [ ] **Step 3: Commit implementation**

```bash
git add laravel/app/Http/Controllers/Organizer/OrganizerRefereeAccountController.php laravel/app/Repositories/Organizer/OrganizerRefereeAccountRepository.php laravel/app/Services/Organizer/OrganizerRefereeAccountService.php laravel/resources/views/organizer/referee-accounts.blade.php laravel/app/Services/Shared/AuthService.php laravel/routes/web.php laravel/routes/api.php
git commit -m "feat: port organizer referee account routes to Laravel"
```
