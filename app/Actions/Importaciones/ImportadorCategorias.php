<?php

namespace App\Actions\Importaciones;

use App\Enums\Modulo;
use App\Models\Categoria;
use App\Models\Importacion;

/**
 * Importa categorías (las subcategorías tienen su propio importador).
 *
 * Crear: cada fila trae el nombre de una categoría; la que ya existe se
 * omite, así que el mismo archivo se puede importar varias veces sin duplicar.
 *
 * Actualizar: renombra categorías existentes. Cada fila trae el ID de la
 * categoría y su nombre nuevo; el archivo exportado ya trae esos IDs.
 */
class ImportadorCategorias extends Importador
{
    /** @var array<string, true>|null Nombres de las categorías del tenant, en minúsculas. */
    private ?array $existentes = null;

    public function modulo(): Modulo
    {
        return Modulo::Categorias;
    }

    public function etiqueta(): string
    {
        return 'Categorías';
    }

    public function columnas(): array
    {
        return [
            'id_categoria' => 'ID categoría',
            'categoria' => 'Categoría',
        ];
    }

    public function sinonimos(): array
    {
        return [
            'categoria' => ['nombre categoria', 'nombre_categoria', 'nombre', 'category'],
        ];
    }

    public function columnaId(): string
    {
        return 'id_categoria';
    }

    public function columnasRequeridas(string $modo): array
    {
        return $modo === Importacion::MODO_CREAR ? ['categoria'] : ['id_categoria', 'categoria'];
    }

    public function reglas(string $modo): array
    {
        return [
            'id_categoria' => [$modo === Importacion::MODO_ACTUALIZAR ? 'required' : 'nullable', 'integer'],
            'categoria' => ['required', 'string', 'max:100'],
        ];
    }

    public function guardar(array $datos, string $modo): bool
    {
        return $modo === Importacion::MODO_ACTUALIZAR
            ? $this->actualizar($datos)
            : $this->crear($datos);
    }

    public function ejemplos(): array
    {
        return [['', 'Herramientas'], ['', 'Pinturas']];
    }

    /**
     * @param  array<string, string|null>  $datos
     */
    private function crear(array $datos): bool
    {
        $this->existentes ??= Categoria::query()
            ->pluck('nombre_categoria')
            ->mapWithKeys(fn (string $nombre): array => [mb_strtolower($nombre) => true])
            ->all();

        $clave = mb_strtolower($datos['categoria']);

        if (isset($this->existentes[$clave])) {
            return false;
        }

        Categoria::create(['nombre_categoria' => $datos['categoria']]);
        $this->existentes[$clave] = true;

        return true;
    }

    /**
     * @param  array<string, string|null>  $datos
     */
    private function actualizar(array $datos): bool
    {
        $categoria = Categoria::query()->find($datos['id_categoria'])
            ?? throw new FilaRechazada("No existe una categoría con ID {$datos['id_categoria']}.");

        if ($categoria->nombre_categoria === $datos['categoria']) {
            return false;
        }

        if (Categoria::query()->whereKeyNot($categoria->id)->where('nombre_categoria', $datos['categoria'])->exists()) {
            throw new FilaRechazada("Ya existe otra categoría llamada {$datos['categoria']}.");
        }

        $categoria->update(['nombre_categoria' => $datos['categoria']]);

        return true;
    }
}
