<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\LegacySessionUser;
use Illuminate\Http\Response;

final class AdminDashboardController extends Controller
{
    public function page(): Response
    {
        return response()->view('admin.dashboard', [
            'pageTitle' => 'VTMS - Quan tri',
            'moduleTitle' => 'Tong quan quan tri',
            'user' => LegacySessionUser::user(),
        ]);
    }
}
