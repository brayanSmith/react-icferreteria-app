<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\Modulo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

/**
 * Comportamiento común de los CRUD de gestión del tenant (catálogos).
 *
 * Cada controlador declara a qué módulo pertenece y este trait traduce el
 * módulo en permisos por acción, además de ofrecer los avisos de resultado
 * (toast) que usan todas las pantallas.
 */
trait EsCrudDeGestion
{
    /**
     * Permisos exigidos por acción del controlador:
     * ver → index, crear → store, editar → update y eliminar → destroy/restore.
     *
     * Se usa desde `HasMiddleware::middleware()`. Un administrador de tenant
     * pasa siempre, porque el acceso total lo concede `Gate::before`.
     *
     * @param  list<string>  $accionesDeVer  Acciones extra que solo requieren "ver".
     * @return list<Middleware>
     */
    protected static function permisosCrud(Modulo $modulo, array $accionesDeVer = []): array
    {
        return [
            new Middleware("can:{$modulo->value}.ver", only: ['index', ...$accionesDeVer]),
            new Middleware("can:{$modulo->value}.crear", only: ['store']),
            new Middleware("can:{$modulo->value}.editar", only: ['update']),
            new Middleware("can:{$modulo->value}.eliminar", only: ['destroy', 'restore']),
        ];
    }

    /**
     * Avisa que la operación salió bien; el mensaje se muestra como toast.
     */
    protected function avisarExito(string $mensaje): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $mensaje]);
    }

    /**
     * Cancela una eliminación porque el registro todavía se usa, y lo explica.
     *
     * Varias claves foráneas de la base borran en cascada (por ejemplo, una
     * ruta arrastraría a sus clientes), así que estas comprobaciones evitan
     * perder datos por accidente.
     */
    protected function rechazarEliminacion(string $razon, string $rutaDeRetorno): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $razon]);

        return redirect()->back(fallback: route($rutaDeRetorno));
    }
}
