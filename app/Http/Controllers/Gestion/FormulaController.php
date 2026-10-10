<?php

namespace App\Http\Controllers\Gestion;

use App\Actions\Formulas\GuardarFormula;
use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarFormulaRequest;
use App\Http\Resources\FormulaResource;
use App\Models\Formula;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Fórmulas (recetas) de producción del tenant.
 *
 * Cada fórmula se guarda junto con su detalle de ingredientes en una sola
 * operación: el formulario envía ambos y GuardarFormula los persiste en una
 * transacción.
 */
class FormulaController extends Controller implements HasMiddleware
{
    use EsCrudDeGestion;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return self::permisosCrud(Modulo::Formulas);
    }

    /**
     * Lista todas las fórmulas: la búsqueda, el orden y la paginación las hace
     * el navegador.
     */
    public function index(): Response
    {
        $formulas = Formula::query()
            // Un producto eliminado sigue apareciendo en las fórmulas que ya lo usaban.
            ->with(['detalleFormulas.producto' => fn ($query) => $query->withTrashed()->with('medida:id,nombre_medida')])
            ->orderBy('nombre_formula')
            ->get();

        return Inertia::render('gestion/formulas/index', [
            'formulas' => FormulaResource::collection($formulas),
            // Una fórmula es una receta: solo se ofrecen insumos (materia prima).
            'productos' => Producto::query()
                ->materiaPrima()
                ->with('medida:id,nombre_medida')
                ->orderBy('nombre_producto')
                ->get(['id', 'codigo_producto', 'nombre_producto', 'medida_id'])
                ->map(fn (Producto $producto): array => [
                    'id' => $producto->id,
                    'nombre' => "{$producto->codigo_producto} - {$producto->nombre_producto}",
                    'medida' => $producto->medida?->nombre_medida,
                ]),
        ]);
    }

    /**
     * Crea la fórmula con su detalle.
     */
    public function store(GuardarFormulaRequest $request, GuardarFormula $guardarFormula): RedirectResponse
    {
        $guardarFormula->handle(null, $request->validated());

        $this->avisarExito('Fórmula creada.');

        return to_route('gestion.formulas.index');
    }

    /**
     * Actualiza la fórmula y sincroniza su detalle.
     */
    public function update(GuardarFormulaRequest $request, Formula $formula, GuardarFormula $guardarFormula): RedirectResponse
    {
        $guardarFormula->handle($formula, $request->validated());

        $this->avisarExito('Fórmula actualizada.');

        return to_route('gestion.formulas.index');
    }

    /**
     * Elimina una fórmula con su detalle, salvo que ya tenga producciones: la
     * base borraría esas producciones en cascada.
     */
    public function destroy(Formula $formula): RedirectResponse
    {
        if ($formula->producciones()->exists()) {
            return $this->rechazarEliminacion(
                'No puedes eliminar esta fórmula porque ya tiene producciones registradas.',
                'gestion.formulas.index',
            );
        }

        $formula->delete();

        $this->avisarExito('Fórmula eliminada.');

        return to_route('gestion.formulas.index');
    }
}
