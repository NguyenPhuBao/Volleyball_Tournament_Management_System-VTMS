<?php

use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrganizerChangeRequestController;
use App\Http\Controllers\Admin\AdminSystemLogController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Organizer\OrganizerCoachAccountController;
use App\Http\Controllers\Organizer\OrganizerDashboardController;
use App\Http\Controllers\Organizer\OrganizerVenueController;
use App\Http\Controllers\PublicSite\CoachRegistrationController;
use App\Http\Controllers\PublicSite\RefereeRegistrationController;
use App\Http\Controllers\Shared\AccountSecurityController;
use App\Http\Controllers\Shared\AuthController;
use App\Http\Controllers\Shared\DashboardController;
use App\Http\Controllers\Shared\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::get('/login', [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/coach/register', [CoachRegistrationController::class, 'page']);
Route::get('/huanluyenvien/dang-ky', [CoachRegistrationController::class, 'page']);
Route::get('/referee/register', [RefereeRegistrationController::class, 'page']);
Route::get('/trong-tai/dang-ky', [RefereeRegistrationController::class, 'page']);
Route::get('/trongtai/dang-ky', [RefereeRegistrationController::class, 'page']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('legacy.auth');
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('legacy.auth');
Route::get('/tai-khoan/doi-mat-khau', [AccountSecurityController::class, 'page'])->middleware('legacy.auth');
Route::get('/account/change-password', [AccountSecurityController::class, 'page'])->middleware('legacy.auth');

Route::middleware(['legacy.auth', 'legacy.role:ADMIN'])->group(function (): void {
    Route::get('/admin', [AdminDashboardController::class, 'page']);
    Route::get('/admin/users', [AdminAccountController::class, 'page']);
    Route::get('/admin/nguoi-dung', [AdminUserController::class, 'page']);
    Route::get('/admin/logs', [AdminSystemLogController::class, 'page']);
    Route::get('/admin/xac-nhan-thong-tin-btc', [AdminOrganizerChangeRequestController::class, 'page']);
});

Route::middleware(['legacy.auth', 'legacy.role:BAN_TO_CHUC,ADMIN'])->group(function (): void {
    Route::get('/ban-to-chuc', [OrganizerDashboardController::class, 'page']);
});

Route::middleware(['legacy.auth', 'legacy.role:BAN_TO_CHUC'])->group(function (): void {
    Route::get('/ban-to-chuc/tai-khoan-hlv', [OrganizerCoachAccountController::class, 'page']);
    Route::get('/ban-to-chuc/san-dau', [OrganizerVenueController::class, 'page']);
});
