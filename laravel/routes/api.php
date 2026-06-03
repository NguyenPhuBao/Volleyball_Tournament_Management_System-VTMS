<?php

use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminOrganizerChangeRequestController;
use App\Http\Controllers\Admin\AdminSystemLogController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Shared\AccountSecurityController;
use App\Http\Controllers\Shared\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'apiLogin']);
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
