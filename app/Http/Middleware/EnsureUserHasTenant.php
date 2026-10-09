<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las pantallas de gestión interna pertenecen a un tenant: el super
 * administrador (sin tenant) no entra, porque no tendría datos que gestionar.
 */
class EnsureUserHasTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->tenant_id === null, 403);

        return $next($request);
    }
}
