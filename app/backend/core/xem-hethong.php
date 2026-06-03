<?php

declare(strict_types=1);

namespace App\Backend\Core;

use App\Backend\Core\Http\Response;
use RuntimeException;

final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'main', int $status = 200): Response
    {
        $viewFile = self::resolveView($view);

        if (!is_file($viewFile)) {
            throw new RuntimeException("View [$view] was not found.");
        }

        $content = self::capture($viewFile, $data);

        if ($layout !== null) {
            $layoutMap = $GLOBALS['VTMS_FILE_MAP']['layouts'] ?? [];
            $layoutPath = $layoutMap[$layout] ?? $layout . '.php';
            $layoutFile = LAYOUT_PATH . '/' . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $layoutPath), '/\\');

            if (!is_file($layoutFile)) {
                throw new RuntimeException("Layout [$layout] was not found.");
            }

            $content = self::capture($layoutFile, array_merge($data, [
                'content' => $content,
            ]));
        }

        return new Response($content, $status);
    }

    private static function resolveView(string $view): string
    {
        $viewMap = $GLOBALS['VTMS_FILE_MAP']['views'] ?? [];

        if (isset($viewMap[$view])) {
            return VIEW_PATH . '/' . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $viewMap[$view]), '/\\');
        }

        $view = str_replace('.', '/', $view);

        return VIEW_PATH . '/' . ltrim($view, '/\\') . '.php';
    }

    private static function capture(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require $file;

        return (string) ob_get_clean();
    }
}
