<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Un tenant restaurado se modela como recurso propio (POST crea la restauración).
 */
class RestoredTenantController extends Controller
{
    public function store(Tenant $tenant): RedirectResponse
    {
        $tenant->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tenant restaurado.']);

        return to_route('admin.tenants.index');
    }
}
