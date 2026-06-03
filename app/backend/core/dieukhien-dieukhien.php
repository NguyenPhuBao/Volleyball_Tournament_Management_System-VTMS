<?php

declare(strict_types=1);

namespace App\Backend\Core;

use App\Backend\Core\Http\Response;

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'main', int $status = 200): Response
    {
        return View::render($view, $data, $layout, $status);
    }

    protected function redirect(string $path, int $status = 302): Response
    {
        return Response::redirect($path, $status);
    }
}
