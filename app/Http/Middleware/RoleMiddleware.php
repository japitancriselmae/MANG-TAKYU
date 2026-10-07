<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->employee || !$user->employee->role) {
            abort(403, 'Your account does not have a valid role.');
        }

        $roleName = $user->employee->role->role_name;

        // Admin is a SUPERUSER — passes ALL role checks
        if ($roleName === 'Admin') {
            return $next($request);
        }

        if (!in_array($roleName, $roles)) {
            abort(403, 'You are not authorized to access this page.');
        }

        return $next($request);
    }
}