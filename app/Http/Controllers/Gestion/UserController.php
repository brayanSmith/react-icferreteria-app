<?php

namespace App\Http\Controllers\Gestion;

use App\Actions\Tenants\CreateTenantUser;
use App\Actions\Tenants\UpdateTenantUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\StoreUserRequest;
use App\Http\Requests\Gestion\UpdateUserRequest;
use App\Http\Resources\TenantUserResource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Personal de un tenant. Los administradores del tenant no aparecen aquí: los
 * gestiona el super administrador de la plataforma.
 */
class UserController extends Controller implements HasMiddleware
{
    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:usuarios.ver', only: ['index']),
            new Middleware('can:usuarios.crear', only: ['store']),
            new Middleware('can:usuarios.editar', only: ['update']),
            new Middleware('can:usuarios.eliminar', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $usuarios = User::query()
            ->where('tenant_id', $tenantId)
            ->where('es_admin', false)
            ->with('roles:id,name')
            ->orderBy('name')
            ->get();

        return Inertia::render('gestion/usuarios/index', [
            'usuarios' => TenantUserResource::collection($usuarios),
            'roles' => Role::query()
                ->where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreUserRequest $request, CreateTenantUser $crearUsuario): RedirectResponse
    {
        $crearUsuario->handle($request->user()->tenant_id, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario creado.']);

        return to_route('gestion.usuarios.index');
    }

    public function update(UpdateUserRequest $request, User $usuario, UpdateTenantUser $actualizarUsuario): RedirectResponse
    {
        $this->asegurarUsuarioGestionable($request, $usuario);

        $actualizarUsuario->handle($usuario, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario actualizado.']);

        return to_route('gestion.usuarios.index');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        $this->asegurarUsuarioGestionable($request, $usuario);

        if ($usuario->is($request->user())) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No puedes eliminar tu propio usuario.']);

            return to_route('gestion.usuarios.index');
        }

        $usuario->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario eliminado.']);

        return to_route('gestion.usuarios.index');
    }

    /**
     * Solo el personal del propio tenant es gestionable; un usuario de otro
     * tenant, o un administrador, se trata como inexistente.
     */
    private function asegurarUsuarioGestionable(Request $request, User $usuario): void
    {
        abort_unless(
            $usuario->tenant_id === $request->user()->tenant_id && ! $usuario->es_admin,
            404,
        );
    }
}
