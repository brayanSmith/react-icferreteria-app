<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tenants\CreateTenantWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = [
            'buscar' => (string) $request->query('buscar', ''),
            'eliminados' => $request->boolean('eliminados'),
        ];

        $tenants = Tenant::query()
            ->withCount('users')
            ->when($filtros['eliminados'], fn ($query) => $query->onlyTrashed())
            ->buscar($filtros['buscar'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/tenants/index', [
            'tenants' => TenantResource::collection($tenants),
            'filtros' => $filtros,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/tenants/create');
    }

    public function store(StoreTenantRequest $request, CreateTenantWithAdmin $crearTenant): RedirectResponse
    {
        $datos = $request->validated();

        $crearTenant->handle(Arr::except($datos, 'admins'), $datos['admins']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenant creado con sus administradores.']);

        return to_route('admin.tenants.index');
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('admin/tenants/edit', [
            'tenant' => new TenantResource($tenant),
            'administradores' => $tenant->admins()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'n_documento', 'debe_cambiar_password']),
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenant actualizado.']);

        return to_route('admin.tenants.index');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenant->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenant eliminado.']);

        return to_route('admin.tenants.index');
    }
}
