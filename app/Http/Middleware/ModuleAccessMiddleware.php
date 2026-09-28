<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleAccessMiddleware
{
    public function handle(Request $request, Closure $next, string $module, string $action = 'read'): Response
    {
        if (! auth()->check()) {
            abort(403, 'Unberechtigt.');
        }

        $user = auth()->user();

        if ($user->canAccessModule($module, $action)) {
            return $next($request);
        }

        abort(403, 'Sie haben keinen Zugriff auf dieses Modul.');
    }
}
