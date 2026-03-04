<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function __construct(
        private readonly ?string $roles = null
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $roles = $this->roles ?? $this->parseRolesFromRoute($request);
        if ($roles === null || $roles === '') {
            return response()->json(['message' => 'Forbidden. Role middleware misconfigured.'], 403);
        }

        $allowed = array_map('trim', explode(',', $roles));
        if (! in_array($request->user()->role, $allowed, true)) {
            return response()->json(['message' => 'Forbidden. Required role: ' . $roles], 403);
        }

        return $next($request);
    }

    private function parseRolesFromRoute(Request $request): ?string
    {
        $route = $request->route();
        if (! $route instanceof Route) {
            return null;
        }
        $middleware = $route->middleware();
        foreach ($middleware as $m) {
            if (is_string($m) && str_starts_with($m, 'role:')) {
                return substr($m, 5);
            }
        }
        return null;
    }
}
