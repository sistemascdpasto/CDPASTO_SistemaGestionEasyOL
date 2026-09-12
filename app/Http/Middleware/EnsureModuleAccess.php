<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $module = (string) $request->route('module');
        $role = Role::forModuleSlug($module);

        abort_unless(
            $user && ($user->hasRole(Role::Administrador->value) || ($role && $user->hasRole($role->value))),
            403
        );

        return $next($request);
    }
}
