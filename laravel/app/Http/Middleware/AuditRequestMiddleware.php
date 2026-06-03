<?php

namespace App\Http\Middleware;

use App\Services\Shared\AuditLogService;
use App\Support\LegacySessionUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class AuditRequestMiddleware
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $actorBefore = LegacySessionUser::user();
        $routePath = $request->route()?->uri() ?? $request->path();
        $routeParams = $request->route()?->parameters() ?? [];
        $error = null;

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
            $this->record($request, $routePath, $routeParams, $actorBefore, $error, 500);

            throw $exception;
        }

        $actor = LegacySessionUser::user() ?? $actorBefore;
        $this->record($request, $routePath, $routeParams, $actor, $error, $response->getStatusCode());

        return $response;
    }

    private function record(Request $request, string $routePath, array $routeParams, ?array $actor, ?string $error, int $status): void
    {
        try {
            $this->auditLog->record($request, '/'.ltrim($routePath, '/'), $routeParams, $actor, $error, $status);
        } catch (Throwable) {
            // Audit logging is best effort and must not break user-facing requests.
        }
    }
}
