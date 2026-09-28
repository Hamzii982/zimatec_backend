<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! auth()->check()) {
            abort(403, 'Unberechtigt.');
        }

        $user = auth()->user();

        if ($role === 'admin' && $user->isAdmin()) {
            return $next($request);
        }

        if ($user->role === $role) {
            return $next($request);
        }

        abort(403, 'Unberechtigt.');
    }
}
