<?php

namespace App\Http\Controllers\Gestion;

use App\Actions\Archivos\GuardarArchivoPublico;
use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use App\Models\Ruta;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Clientes del tenant.
 *
 * Se listan con búsqueda y paginación, se eliminan de forma lógica y se pueden
 * restaurar. Cada cliente puede tener una ruta de reparto, un comercial
 * asignado y su RUT adjunto.
 */
class ClienteController extends Controller implements HasMiddleware
{
    use EsCrudDeGestion;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return self::permisosCrud(Modulo::Clientes);
    }

    /**
     * Lista los clientes, filtrando por razón social, documento, correo o
     * teléfono, y opcionalmente solo los eliminados. Incluye las rutas y los
     * comerciales disponibles para el formulario.
     */
    public function index(Request $request): Response
    {
        $filtros = [
            'buscar' => (string) $request->query('buscar', ''),
            'eliminados' => $request->boolean('eliminados'),
        ];

        $clientes = Cliente::query()
            ->with(['ruta:id,ruta', 'comercial:id,name'])
            ->when($filtros['eliminados'], fn ($query) => $query->onlyTrashed())
            ->when($filtros['buscar'] !== '', fn ($query) => $query->where(function ($query) use ($filtros): void {
                $query->where('razon_social', 'like', "%{$filtros['buscar']}%")
                    ->orWhere('numero_documento', 'like', "%{$filtros['buscar']}%")
                    ->orWhere('email', 'like', "%{$filtros['buscar']}%")
                    ->orWhere('telefono', 'like', "%{$filtros['buscar']}%");
            }))
            ->orderBy('razon_social')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('gestion/clientes/index', [
            'clientes' => ClienteResource::collection($clientes),
            'filtros' => $filtros,
            'rutas' => Ruta::query()
                ->orderBy('ruta')
                ->get(['id', 'ruta'])
                ->map(fn (Ruta $ruta): array => ['id' => $ruta->id, 'nombre' => $ruta->ruta]),
            // Cualquier usuario del tenant puede ser el comercial de un cliente.
            'comerciales' => User::query()
                ->where('tenant_id', $request->user()->tenant_id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $usuario): array => ['id' => $usuario->id, 'nombre' => $usuario->name]),
        ]);
    }

    /**
     * Crea un cliente y guarda su RUT si se adjuntó.
     */
    public function store(GuardarClienteRequest $request, GuardarArchivoPublico $archivos): RedirectResponse
    {
        Cliente::create($this->datosParaGuardar($request, $archivos));

        $this->avisarExito('Cliente creado.');

        return to_route('gestion.clientes.index');
    }

    /**
     * Actualiza un cliente; reemplaza o quita su RUT según lo enviado.
     */
    public function update(GuardarClienteRequest $request, Cliente $cliente, GuardarArchivoPublico $archivos): RedirectResponse
    {
        $cliente->update($this->datosParaGuardar($request, $archivos, $cliente));

        $this->avisarExito('Cliente actualizado.');

        return to_route('gestion.clientes.index');
    }

    /**
     * Elimina (de forma lógica) un cliente, salvo que todavía tenga pedidos
     * pendientes de pago: no se puede perder de vista a quien debe dinero.
     */
    public function destroy(Cliente $cliente): RedirectResponse
    {
        $conSaldoPendiente = $cliente->estadoPagoEnCartera()
            ->where('estado', '!=', 'ANULADO')
            ->exists();

        if ($conSaldoPendiente) {
            return $this->rechazarEliminacion(
                'No puedes eliminar a este cliente porque tiene pedidos pendientes de pago.',
                'gestion.clientes.index',
            );
        }

        $cliente->delete();

        $this->avisarExito('Cliente eliminado.');

        return to_route('gestion.clientes.index');
    }

    /**
     * Restaura un cliente eliminado.
     */
    public function restore(Cliente $cliente): RedirectResponse
    {
        $cliente->restore();

        $this->avisarExito('Cliente restaurado.');

        return to_route('gestion.clientes.index');
    }

    /**
     * Separa los campos del cliente de los del archivo adjunto y resuelve la
     * ruta final del RUT.
     *
     * @return array<string, mixed>
     */
    private function datosParaGuardar(GuardarClienteRequest $request, GuardarArchivoPublico $archivos, ?Cliente $cliente = null): array
    {
        $datos = Arr::except($request->validated(), ['rut', 'quitar_rut']);

        $datos['rut_imagen'] = $archivos->reemplazar(
            $cliente?->rut_imagen,
            $request->file('rut'),
            $request->boolean('quitar_rut'),
            "clientes/{$request->user()->tenant_id}",
        );

        return $datos;
    }
}
