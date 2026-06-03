<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Support\LegacySessionUser;
use Illuminate\Http\Response;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        return response()->view('dashboard.index', [
            'pageTitle' => 'VTMS - Dashboard',
            'moduleTitle' => 'Dashboard',
            'user' => LegacySessionUser::user(),
        ]);
    }
}
