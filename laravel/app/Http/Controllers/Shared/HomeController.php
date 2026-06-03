<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Support\LegacySessionUser;
use Illuminate\Http\Response;

final class HomeController extends Controller
{
    public function index(): Response
    {
        return response()->view('public.home', [
            'user' => LegacySessionUser::user(),
        ]);
    }
}
