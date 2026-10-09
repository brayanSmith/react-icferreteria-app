<?php

namespace App\Http\Controllers\Gestion;

use App\Actions\Archivos\GuardarArchivoPublico;
use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarProveedorRequest;
use App\Http\Resources\ProveedorResource;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Proveedores a los que el tenant les compra.
 *
 * Se listan con búsqueda y paginación, se eliminan de forma lógica y se pueden
 * restaurar. Pueden llevar adjunto su RUT (PDF o imagen).
 */
class ProveedorController extends Controller implements HasMiddleware
{
    use EsCrudDeGestion;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return self::permisosCrud(Modulo::Proveedores);
    }

    /**
     * Lista los proveedores, filtrando por nombre, razón social o NIT, y
     * opcionalmente solo los eliminados.
     */
    public function index(Request $request): Response
    {
        $filtros = [
            'buscar' => (string) $request->query('buscar', ''),
            'eliminados' => $request->boolean('eliminados'),
        ];

        $proveedores = Proveedor::query()
            ->when($filtros['eliminados'], fn ($query) => $query->onlyTrashed())
            ->when($filtros['buscar'] !== '', fn ($query) => $query->where(function ($query) use ($filtros): void {
                $query->where('nombre_proveedor', 'like', "%{$filtros['buscar']}%")
                    ->orWhere('razon_social_proveedor', 'like', "%{$filtros['buscar']}%")
                    ->orWhere('nit_proveedor', 'like', "%{$filtros['buscar']}%");
            }))
            ->orderBy('nombre_proveedor')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('gestion/proveedores/index', [
            'proveedores' => ProveedorResource::collection($proveedores),
            'filtros' => $filtros,
        ]);
    }

    /**
     * Crea un proveedor y guarda su RUT si se adjuntó.
     */
    public function store(GuardarProveedorRequest $request, GuardarArchivoPublico $archivos): RedirectResponse
    {
        Proveedor::create($this->datosParaGuardar($request, $archivos));

        $this->avisarExito('Proveedor creado.');

        return to_route('gestion.proveedores.index');
    }

    /**
     * Actualiza un proveedor; reemplaza o quita su RUT según lo enviado.
     */
    public function update(GuardarProveedorRequest $request, Proveedor $proveedor, GuardarArchivoPublico $archivos): RedirectResponse
    {
        $proveedor->update($this->datosParaGuardar($request, $archivos, $proveedor));

        $this->avisarExito('Proveedor actualizado.');

        return to_route('gestion.proveedores.index');
    }

    /**
     * Elimina (de forma lógica) un proveedor, salvo que ya tenga compras:
     * conservar el historial de compras exige conservar al proveedor activo.
     */
    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        if ($proveedor->compras()->withTrashed()->exists()) {
            return $this->rechazarEliminacion(
                'No puedes eliminar este proveedor porque tiene compras registradas.',
                'gestion.proveedores.index',
            );
        }

        $proveedor->delete();

        $this->avisarExito('Proveedor eliminado.');

        return to_route('gestion.proveedores.index');
    }

    /**
     * Restaura un proveedor eliminado.
     */
    public function restore(Proveedor $proveedor): RedirectResponse
    {
        $proveedor->restore();

        $this->avisarExito('Proveedor restaurado.');

        return to_route('gestion.proveedores.index');
    }

    /**
     * Separa los campos del proveedor de los del archivo adjunto y resuelve
     * la ruta final del RUT.
     *
     * @return array<string, mixed>
     */
    private function datosParaGuardar(GuardarProveedorRequest $request, GuardarArchivoPublico $archivos, ?Proveedor $proveedor = null): array
    {
        $datos = Arr::except($request->validated(), ['rut', 'quitar_rut']);

        $datos['rut_proveedor_imagen'] = $archivos->reemplazar(
            $proveedor?->rut_proveedor_imagen,
            $request->file('rut'),
            $request->boolean('quitar_rut'),
            "proveedores/{$request->user()->tenant_id}",
        );

        return $datos;
    }
}
