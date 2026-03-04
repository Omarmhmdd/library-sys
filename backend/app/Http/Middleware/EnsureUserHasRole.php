<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function __construct(
        private readonly string $roles
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $allowed = array_map('trim', explode(',', $this->roles));
        if (! in_array($request->user()->role, $allowed, true)) {
            return response()->json(['message' => 'Forbidden. Required role: '.$this->roles], 403);
        }

        return $next($request);
    }
}
