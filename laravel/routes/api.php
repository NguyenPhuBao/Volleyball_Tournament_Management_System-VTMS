<?php

use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminOrganizerChangeRequestController;
use App\Http\Controllers\Admin\AdminSystemLogController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Organizer\OrganizerCoachAccountController;
use App\Http\Controllers\Organizer\OrganizerVenueController;
use App\Http\Controllers\PublicSite\CoachRegistrationController;
use App\Http\Controllers\PublicSite\RefereeRegistrationController;
use App\Http\Controllers\Shared\AccountSecurityController;
use App\Http\Controllers\Shared\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'apiLogin']);
Route::get('/coach/register/options', [CoachRegistrationController::class, 'options']);
Route::post('/auth/register/coach', [CoachRegistrationController::class, 'store']);
Route::post('/register/coach', [CoachRegistrationController::class, 'store']);
Route::post('/coach/register', [CoachRegistrationController::class, 'store']);
Route::post('/coaches/register', [CoachRegistrationController::class, 'store']);
Route::post('/huan-luyen-vien/register', [CoachRegistrationController::class, 'store']);
Route::post('/huanluyenvien/register', [CoachRegistrationController::class, 'store']);
Route::get('/referee/register/options', [RefereeRegistrationController::class, 'options']);
Route::post('/auth/register/referee', [RefereeRegistrationController::class, 'store']);
Route::post('/register/referee', [RefereeRegistrationController::class, 'store']);
Route::post('/referee/register', [RefereeRegistrationController::class, 'store']);
Route::post('/referees/register', [RefereeRegistrationController::class, 'store']);
Route::post('/trong-tai/register', [RefereeRegistrationController::class, 'store']);
Route::post('/trongtai/register', [RefereeRegistrationController::class, 'store']);
Route::post('/auth/logout', [AuthController::class, 'apiLogout'])->middleware('legacy.auth');
Route::get('/auth/me', [AuthController::class, 'apiMe'])->middleware('legacy.auth');
Route::post('/account/password', [AccountSecurityController::class, 'changePassword'])->middleware('legacy.auth');
Route::post('/auth/change-password', [AccountSecurityController::class, 'changePassword'])->middleware('legacy.auth');

Route::middleware(['legacy.auth', 'legacy.role:ADMIN'])->group(function (): void {
    Route::get('/admin/roles', [AdminAccountController::class, 'roles']);
    Route::get('/admin/accounts', [AdminAccountController::class, 'index']);
    Route::post('/admin/accounts', [AdminAccountController::class, 'store']);
    Route::get('/admin/accounts/{id}', [AdminAccountController::class, 'show'])->whereNumber('id');
    Route::match(['put', 'patch'], '/admin/accounts/{id}', [AdminAccountController::class, 'update'])->whereNumber('id');
    Route::post('/admin/accounts/{id}/update', [AdminAccountController::class, 'update'])->whereNumber('id');
    Route::delete('/admin/accounts/{id}', [AdminAccountController::class, 'destroy'])->whereNumber('id');
    Route::post('/admin/accounts/{id}/delete', [AdminAccountController::class, 'destroy'])->whereNumber('id');

    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::get('/admin/users/{id}', [AdminUserController::class, 'show'])->whereNumber('id');
    Route::match(['put', 'patch'], '/admin/users/{id}', [AdminUserController::class, 'update'])->whereNumber('id');
    Route::post('/admin/users/{id}/update', [AdminUserController::class, 'update'])->whereNumber('id');

    Route::get('/admin/system-logs', [AdminSystemLogController::class, 'index']);
    Route::get('/admin/system-logs/options', [AdminSystemLogController::class, 'options']);
    Route::get('/admin/system-logs/{id}', [AdminSystemLogController::class, 'show'])->whereNumber('id');

    Route::get('/admin/organizer-change-requests', [AdminOrganizerChangeRequestController::class, 'index']);
    Route::get('/admin/organizer-change-requests/{id}', [AdminOrganizerChangeRequestController::class, 'show'])->whereNumber('id');
    Route::post('/admin/organizer-change-requests/{id}/approve', [AdminOrganizerChangeRequestController::class, 'approve'])->whereNumber('id');
    Route::post('/admin/organizer-change-requests/{id}/reject', [AdminOrganizerChangeRequestController::class, 'reject'])->whereNumber('id');
});

Route::middleware(['legacy.auth', 'legacy.role:BAN_TO_CHUC'])->group(function (): void {
    Route::get('/organizer/coach-accounts', [OrganizerCoachAccountController::class, 'index']);
    Route::get('/organizer/coach-accounts/{accountId}', [OrganizerCoachAccountController::class, 'show'])->whereNumber('accountId');
    Route::post('/organizer/coach-accounts/{accountId}/approve', [OrganizerCoachAccountController::class, 'approve'])->whereNumber('accountId');
    Route::post('/organizer/coach-accounts/{accountId}/reject', [OrganizerCoachAccountController::class, 'reject'])->whereNumber('accountId');

    Route::get('/organizer/competition-locations', [OrganizerVenueController::class, 'locations']);
    Route::get('/organizer/venues', [OrganizerVenueController::class, 'index']);
    Route::post('/organizer/venues', [OrganizerVenueController::class, 'store']);
    Route::get('/organizer/venues/{venueId}', [OrganizerVenueController::class, 'show'])->whereNumber('venueId');
    Route::match(['put', 'patch'], '/organizer/venues/{venueId}', [OrganizerVenueController::class, 'update'])->whereNumber('venueId');
    Route::post('/organizer/venues/{venueId}/update', [OrganizerVenueController::class, 'update'])->whereNumber('venueId');
    Route::post('/organizer/venues/{venueId}/deactivate', [OrganizerVenueController::class, 'deactivate'])->whereNumber('venueId');
    Route::post('/organizer/venues/{venueId}/remove', [OrganizerVenueController::class, 'deactivate'])->whereNumber('venueId');
    Route::delete('/organizer/venues/{venueId}', [OrganizerVenueController::class, 'deactivate'])->whereNumber('venueId');
    Route::post('/organizer/venues/{venueId}/delete', [OrganizerVenueController::class, 'deactivate'])->whereNumber('venueId');
});
