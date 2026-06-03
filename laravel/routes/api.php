<?php

use App\Http\Controllers\Shared\AccountSecurityController;
use App\Http\Controllers\Shared\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'apiLogin']);
Route::post('/auth/logout', [AuthController::class, 'apiLogout'])->middleware('legacy.auth');
Route::get('/auth/me', [AuthController::class, 'apiMe'])->middleware('legacy.auth');
Route::post('/account/password', [AccountSecurityController::class, 'changePassword'])->middleware('legacy.auth');
Route::post('/auth/change-password', [AccountSecurityController::class, 'changePassword'])->middleware('legacy.auth');
