<?php

use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrganizerChangeRequestController;
use App\Http\Controllers\Admin\AdminSystemLogController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Shared\AccountSecurityController;
use App\Http\Controllers\Shared\AuthController;
use App\Http\Controllers\Shared\DashboardController;
use App\Http\Controllers\Shared\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::get('/login', [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login']);
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
