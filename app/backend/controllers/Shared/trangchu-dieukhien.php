<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Shared;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('public.home', [
            'user' => Auth::user(),
        ]);
    }
}

