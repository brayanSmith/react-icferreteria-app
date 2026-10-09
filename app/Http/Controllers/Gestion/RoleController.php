<?php

namespace App\Http\Controllers\Gestion;

use App\Enums\Modulo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\StoreRoleRequest;
use App\Http\Requests\Gestion\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Roles que cada tenant crea para agrupar permisos del catálogo.
 */
class RoleController extends Controller implements HasMiddleware
{
    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:roles.ver', only: ['index']),
            new Middleware('can:roles.crear', only: ['store']),
            new Middleware('can:roles.editar', only: ['update']),
            new Middleware('can:roles.eliminar', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $roles = Role::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->with('permissions:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return Inertia::render('gestion/roles/index', [
            'roles' => RoleResource::collection($roles),
            'catalogo' => Modulo::catalogo(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $rol = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
            'tenant_id' => $request->user()->tenant_id,
        ]);

        $rol->syncPermissions($request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol creado.']);

        return to_route('gestion.roles.index');
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->asegurarRolDelTenant($request, $role);

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol actualizado.']);

        return to_route('gestion.roles.index');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->asegurarRolDelTenant($request, $role);

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol eliminado.']);

        return to_route('gestion.roles.index');
    }

    /**
     * Un rol de otro tenant se trata como inexistente.
     */
    private function asegurarRolDelTenant(Request $request, Role $role): void
    {
        abort_unless($role->tenant_id === $request->user()->tenant_id, 404);
    }
}
