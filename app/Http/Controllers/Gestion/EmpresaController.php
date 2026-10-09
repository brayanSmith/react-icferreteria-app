<?php

namespace App\Http\Controllers\Gestion;

use App\Actions\Empresas\GuardarEmpresa;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\UpdateEmpresaRequest;
use App\Http\Resources\EmpresaResource;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Datos de la empresa del tenant. Cada tenant tiene una sola, así que no hay
 * listado ni creación o eliminación: se crea al primer guardado y se edita.
 */
class EmpresaController extends Controller implements HasMiddleware
{
    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:empresa.ver', only: ['edit']),
            new Middleware('can:empresa.editar', only: ['update']),
        ];
    }

    public function edit(Request $request): Response
    {
        $empresa = Empresa::query()->first();

        return Inertia::render('gestion/empresa/edit', [
            'empresa' => $empresa
                ? new EmpresaResource($empresa)
                : null,
            'nombreSugerido' => $request->user()->tenant->nombre,
        ]);
    }

    public function update(UpdateEmpresaRequest $request, GuardarEmpresa $guardarEmpresa): RedirectResponse
    {
        $guardarEmpresa->handle(
            $request->user()->tenant_id,
            $request->validated(),
            $request->file('logo'),
            $request->file('icono'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Datos de la empresa guardados.']);

        return to_route('gestion.empresa.edit');
    }
}
