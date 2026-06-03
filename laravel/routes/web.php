<?php

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
