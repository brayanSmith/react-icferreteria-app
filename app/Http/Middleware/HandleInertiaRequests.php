<?php

namespace App\Http\Middleware;

use App\Enums\Modulo;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Permisos efectivos del usuario, para mostrar u ocultar opciones en el
     * frontend. Los administradores tienen todo el catálogo. La verificación
     * real siempre se hace en el servidor.
     *
     * @return list<string>
     */
    private function permisosDe(?User $usuario): array
    {
        if ($usuario === null) {
            return [];
        }

        if ($usuario->es_super_admin || $usuario->es_admin) {
            return Modulo::permisos();
        }

        return $usuario->getAllPermissions()->pluck('name')->values()->all();
    }

    /**
     * Nombre, logo e ícono de la empresa del usuario, para mostrarlos en el
     * menú, el título y el favicon. Es null si el usuario no tiene tenant (el
     * super administrador) o su tenant aún no registró la empresa.
     *
     * @return array{nombre: string, logo_url: ?string, icono_url: ?string}|null
     */
    private function marcaDeEmpresa(?User $usuario): ?array
    {
        if ($usuario?->tenant_id === null) {
            return null;
        }

        $empresa = Empresa::query()->first();

        return $empresa === null ? null : [
            'nombre' => $empresa->nombre_empresa,
            'logo_url' => $empresa->logoUrl(),
            'icono_url' => $empresa->iconoUrl(),
        ];
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'permisos' => fn (): array => $this->permisosDe($request->user()),
            ],
            'marca' => fn (): ?array => $this->marcaDeEmpresa($request->user()),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
