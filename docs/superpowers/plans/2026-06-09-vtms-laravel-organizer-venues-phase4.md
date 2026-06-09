# VTMS Laravel Organizer Venues Phase 4 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port the Ban to chuc "San dau" module into `laravel/` while preserving legacy routes, response shape, validation, status history, and system logs.

**Architecture:** Add Laravel organizer controllers, services, repositories, Blade pages, and routes. Keep legacy PHP untouched. Scope Phase 4 to the organizer dashboard shell, venue page, venue API, and the competition location dropdown API required by the page.

**Tech Stack:** PHP 8.3, Laravel 12, Laravel feature tests, MySQL via Laravel DB facade, Blade.

---

## File Structure

- Create: `laravel/tests/Feature/OrganizerVenueRouteCompatibilityTest.php`
  - Verifies organizer route aliases exist, auth/role guards match legacy behavior, and organizer pages render.
- Create: `laravel/app/Http/Controllers/Organizer/OrganizerDashboardController.php`
  - Handles `/ban-to-chuc` dashboard shell.
- Create: `laravel/app/Http/Controllers/Organizer/OrganizerVenueController.php`
  - Handles `/ban-to-chuc/san-dau` and venue API endpoints.
- Create: `laravel/app/Repositories/Organizer/OrganizerRepository.php`
  - Encapsulates active organizer lookup and competition location reads.
- Create: `laravel/app/Repositories/Organizer/OrganizerVenueRepository.php`
  - Encapsulates `Sandau`, `Vitrithidau`, `Nhatkyhethong`, and `Nhatkytrangthai` reads/writes.
- Create: `laravel/app/Services/Organizer/OrganizerVenueService.php`
  - Ports legacy venue validation, duplicate guard, status transition, and response shape.
- Create: `laravel/resources/views/organizer/dashboard.blade.php`
- Create: `laravel/resources/views/organizer/venues.blade.php`
- Modify: `laravel/routes/web.php`
  - Add organizer dashboard and venue page routes.
- Modify: `laravel/routes/api.php`
  - Add organizer venue API aliases and `competition-locations`.

---

### Task 1: Organizer Venue Route Tests

- [ ] **Step 1: Write failing route/page/API guard tests**

Cover these legacy paths:

- `ban-to-chuc`
- `ban-to-chuc/san-dau`
- `api/organizer/competition-locations`
- `api/organizer/venues`
- `api/organizer/venues/{venueId}`
- `api/organizer/venues/{venueId}/update`
- `api/organizer/venues/{venueId}/deactivate`
- `api/organizer/venues/{venueId}/remove`
- `api/organizer/venues/{venueId}/delete`

- [ ] **Step 2: Run focused test to verify RED**

Run from `laravel/`:

```bash
../runtime/php-8.3/php.exe artisan test --filter=OrganizerVenueRouteCompatibilityTest
```

Expected: FAIL because organizer venue routes are not ported yet.

- [ ] **Step 3: Commit failing tests**

```bash
git add laravel/tests/Feature/OrganizerVenueRouteCompatibilityTest.php
git commit -m "test: add Laravel organizer venue route tests"
```

---

### Task 2: Organizer Venue Port

- [ ] **Step 1: Implement organizer repositories**

Port these legacy reads/writes:

- `Giaidau::findOrganizerByAccountId()`
- `Giaidau::competitionLocations()`
- `Giaidau::competitionLocationById()`
- `Sandau::list()`
- `Sandau::findById()`
- `Sandau::existsByNameAndLocation()`
- `Sandau::createVenue()`
- `Sandau::updateVenue()`
- `Sandau::deactivateVenue()`

- [ ] **Step 2: Implement organizer venue service**

Port behavior from `OrganizerVenueService`:

- Active organizer guard returns legacy `403`.
- Invalid filters return `422`.
- Create validates name, competition location, capacity, description, and status.
- Update validates changed fields and rejects empty changes.
- Duplicate venue name within a location returns `409`.
- Deactivate maps remove/delete aliases to status `NGUNG_SU_DUNG`.
- Successful writes record `Nhatkytrangthai` and `Nhatkyhethong`.

- [ ] **Step 3: Implement controllers/views/routes**

Web routes:

```php
Route::get('/ban-to-chuc', [OrganizerDashboardController::class, 'page']);
Route::get('/ban-to-chuc/san-dau', [OrganizerVenueController::class, 'page']);
```

API routes:

```php
Route::get('/organizer/competition-locations', [OrganizerVenueController::class, 'locations']);
Route::get('/organizer/venues', [OrganizerVenueController::class, 'index']);
Route::post('/organizer/venues', [OrganizerVenueController::class, 'store']);
Route::get('/organizer/venues/{venueId}', [OrganizerVenueController::class, 'show']);
Route::match(['put', 'patch'], '/organizer/venues/{venueId}', [OrganizerVenueController::class, 'update']);
Route::post('/organizer/venues/{venueId}/update', [OrganizerVenueController::class, 'update']);
Route::post('/organizer/venues/{venueId}/deactivate', [OrganizerVenueController::class, 'deactivate']);
Route::post('/organizer/venues/{venueId}/remove', [OrganizerVenueController::class, 'deactivate']);
Route::delete('/organizer/venues/{venueId}', [OrganizerVenueController::class, 'deactivate']);
Route::post('/organizer/venues/{venueId}/delete', [OrganizerVenueController::class, 'deactivate']);
```

- [ ] **Step 4: Run focused tests**

```bash
../runtime/php-8.3/php.exe artisan test --filter=OrganizerVenueRouteCompatibilityTest
```

Expected: PASS.

---

### Task 3: Full Verification and Commit

- [ ] **Step 1: Run full Laravel tests**

```bash
../runtime/php-8.3/php.exe artisan test
```

- [ ] **Step 2: Inspect organizer routes**

```bash
../runtime/php-8.3/php.exe artisan route:list --path=ban-to-chuc
../runtime/php-8.3/php.exe artisan route:list --path=organizer/venues
```

- [ ] **Step 3: Commit implementation**

```bash
git add laravel/app/Http/Controllers/Organizer laravel/app/Repositories/Organizer laravel/app/Services/Organizer laravel/resources/views/organizer laravel/routes/web.php laravel/routes/api.php
git commit -m "feat: port organizer venue routes to Laravel"
```
