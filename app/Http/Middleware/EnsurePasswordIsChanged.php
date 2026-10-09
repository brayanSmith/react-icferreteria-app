<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obliga a quien ingresa con una contraseña temporal a cambiarla antes de
 * usar el resto de la aplicación.
 */
class EnsurePasswordIsChanged
{
    /**
     * Rutas permitidas mientras la contraseña sigue siendo temporal.
     *
     * @var list<string>
     */
    private const RUTAS_PERMITIDAS = [
        'security.edit',
        'user-password.update',
        'logout',
        'password.*',
        'passkey.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->debe_cambiar_password || $request->routeIs(self::RUTAS_PERMITIDAS)) {
            return $next($request);
        }

        Inertia::flash('toast', [
            'type' => 'warning',
            'message' => 'Debes cambiar tu contraseña temporal para continuar.',
        ]);

        return to_route('security.edit');
    }
}
