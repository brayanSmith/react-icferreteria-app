<?php

namespace App\Actions\Importaciones;

use App\Enums\Modulo;
use App\Models\Categoria;
use App\Models\Importacion;
use App\Models\SubCategoria;

/**
 * Importa subcategorías.
 *
 * Crear: cada fila trae el nombre de la categoría a la que pertenece (que ya
 * debe existir; si no, la fila se rechaza) y el de la subcategoría. La
 * subcategoría que ya existe en esa categoría se omite.
 *
 * Actualizar: renombra subcategorías existentes. Cada fila trae el ID de la
 * subcategoría y su nombre nuevo; el archivo exportado ya trae esos IDs.
 */
class ImportadorSubCategorias extends Importador
{
    /** @var array<string, int>|null Categorías del tenant: nombre en minúsculas => id. */
    private ?array $categorias = null;

    /** @var array<int, array<string, true>> Subcategorías ya conocidas por categoría. */
    private array $subCategorias = [];

    public function modulo(): Modulo
    {
        return Modulo::Categorias;
    }

    public function etiqueta(): string
    {
        return 'Subcategorías';
    }

    public function columnas(): array
    {
        return [
            'id_subcategoria' => 'ID subcategoría',
            'categoria' => 'Categoría',
            'subcategoria' => 'Subcategoría',
        ];
    }

    public function sinonimos(): array
    {
        return [
            'categoria' => ['nombre categoria', 'nombre_categoria', 'category'],
            'subcategoria' => ['sub categoria', 'sub_categoria', 'nombre subcategoria', 'nombre_sub_categoria', 'nombre'],
        ];
    }

    public function columnaId(): string
    {
        return 'id_subcategoria';
    }

    public function columnasRequeridas(string $modo): array
    {
        return $modo === Importacion::MODO_CREAR ? ['categoria', 'subcategoria'] : ['id_subcategoria', 'subcategoria'];
    }

    public function reglas(string $modo): array
    {
        $actualizar = $modo === Importacion::MODO_ACTUALIZAR;

        return [
            'id_subcategoria' => [$actualizar ? 'required' : 'nullable', 'integer'],
            'categoria' => [$actualizar ? 'nullable' : 'required', 'string', 'max:100'],
            'subcategoria' => ['required', 'string', 'max:100'],
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
        return [['', 'Herramientas', 'Manuales'], ['', 'Herramientas', 'Eléctricas']];
    }

    /**
     * @param  array<string, string|null>  $datos
     */
    private function crear(array $datos): bool
    {
        $this->categorias ??= Categoria::query()
            ->pluck('id', 'nombre_categoria')
            ->mapWithKeys(fn (int $id, string $nombre): array => [mb_strtolower($nombre) => $id])
            ->all();

        $categoriaId = $this->categorias[mb_strtolower($datos['categoria'])]
            ?? throw new FilaRechazada("La categoría {$datos['categoria']} no existe. Impórtala primero.");

        $clave = mb_strtolower($datos['subcategoria']);

        if (isset($this->subCategoriasDe($categoriaId)[$clave])) {
            return false;
        }

        SubCategoria::create(['categoria_id' => $categoriaId, 'nombre_sub_categoria' => $datos['subcategoria']]);
        $this->subCategorias[$categoriaId][$clave] = true;

        return true;
    }

    /**
     * @param  array<string, string|null>  $datos
     */
    private function actualizar(array $datos): bool
    {
        // whereHas('categoria') limita la búsqueda a las subcategorías del tenant.
        $subCategoria = SubCategoria::query()->whereHas('categoria')->find($datos['id_subcategoria'])
            ?? throw new FilaRechazada("No existe una subcategoría con ID {$datos['id_subcategoria']}.");

        if ($subCategoria->nombre_sub_categoria === $datos['subcategoria']) {
            return false;
        }

        $repetida = SubCategoria::query()
            ->where('categoria_id', $subCategoria->categoria_id)
            ->whereKeyNot($subCategoria->id)
            ->where('nombre_sub_categoria', $datos['subcategoria'])
            ->exists();

        if ($repetida) {
            throw new FilaRechazada("Ya existe otra subcategoría llamada {$datos['subcategoria']} en esa categoría.");
        }

        $subCategoria->update(['nombre_sub_categoria' => $datos['subcategoria']]);

        return true;
    }

    /**
     * Subcategorías existentes de una categoría (se leen una sola vez).
     *
     * @return array<string, true>
     */
    private function subCategoriasDe(int $categoriaId): array
    {
        return $this->subCategorias[$categoriaId] ??= SubCategoria::query()
            ->where('categoria_id', $categoriaId)
            ->pluck('nombre_sub_categoria')
            ->mapWithKeys(fn (string $nombre): array => [mb_strtolower($nombre) => true])
            ->all();
    }
}
