<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Indica a Spatie Permission en qué tenant se evalúan los roles de esta
 * petición, tomándolo del usuario autenticado.
 */
class SetPermissionsTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($request->user()?->tenant_id);

        return $next($request);
    }
}
