<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Support\LegacySessionUser;
use Illuminate\Http\Response;

final class OrganizerDashboardController extends Controller
{
    public function page(): Response
    {
        return response()->view('organizer.dashboard', [
            'pageTitle' => 'VTMS - Ban to chuc',
            'moduleTitle' => 'Tong quan ban to chuc',
            'user' => LegacySessionUser::user(),
        ]);
    }
}
