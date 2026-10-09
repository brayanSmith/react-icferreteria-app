<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tenants\CreateTenantAdmin;
use App\Actions\Tenants\UpdateTenantAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantAdminRequest;
use App\Http\Requests\Admin\UpdateTenantAdminRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Administradores de un tenant ya existente.
 */
class TenantAdminController extends Controller
{
    public function store(StoreTenantAdminRequest $request, Tenant $tenant, CreateTenantAdmin $crearAdministrador): RedirectResponse
    {
        $crearAdministrador->handle($tenant, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Administrador agregado.']);

        return to_route('admin.tenants.edit', $tenant);
    }

    public function update(UpdateTenantAdminRequest $request, Tenant $tenant, User $admin, UpdateTenantAdmin $actualizarAdministrador): RedirectResponse
    {
        $actualizarAdministrador->handle($admin, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Administrador actualizado.']);

        return to_route('admin.tenants.edit', $tenant);
    }

    /**
     * Elimina (borrado lógico) a un administrador, salvo que sea el último: un
     * tenant sin administradores quedaría sin nadie que gestione sus usuarios.
     */
    public function destroy(Tenant $tenant, User $admin): RedirectResponse
    {
        if ($tenant->admins()->count() <= 1) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No puedes eliminar al único administrador del tenant. Agrega otro primero.',
            ]);

            return to_route('admin.tenants.edit', $tenant);
        }

        $admin->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Administrador eliminado.']);

        return to_route('admin.tenants.edit', $tenant);
    }
}
