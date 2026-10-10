<?php

namespace App\Actions\Importaciones;

use App\Enums\Modulo;
use App\Http\Requests\Gestion\GuardarPucRequest;
use App\Models\Importacion;
use App\Models\Puc;

/**
 * Importa el plan de cuentas (PUC).
 *
 * La subcuenta identifica cada cuenta (es única en el tenant), así que aquí
 * no hace falta un ID: en "actualizar" la fila modifica la cuenta que tenga esa
 * subcuenta, y en "crear y actualizar" la fila actualiza si la subcuenta ya
 * existe y crea si no. Al actualizar solo cambian los datos que la fila trae
 * llenos; una celda vacía deja el valor actual como está.
 */
class ImportadorPucs extends Importador
{
    /** @var array<string, true>|null Subcuentas que ya existen en el tenant. */
    private ?array $subcuentas = null;

    public function modulo(): Modulo
    {
        return Modulo::Pucs;
    }

    public function etiqueta(): string
    {
        return 'Plan de cuentas';
    }

    public function columnas(): array
    {
        return [
            'tipo' => 'Clase',
            'cuenta' => 'Cuenta',
            'subcuenta' => 'Subcuenta',
            'concepto' => 'Concepto',
            'descripcion' => 'Descripción',
        ];
    }

    public function sinonimos(): array
    {
        return [
            'tipo' => ['tipo', 'clase de cuenta'],
            'concepto' => ['nombre', 'nombre cuenta', 'nombre de la cuenta'],
            'descripcion' => ['observaciones', 'detalle'],
        ];
    }

    public function columnaId(): string
    {
        return 'subcuenta';
    }

    public function identificador(): string
    {
        return 'su subcuenta';
    }

    public function columnasRequeridas(string $modo): array
    {
        return $modo === Importacion::MODO_CREAR ? ['tipo', 'cuenta', 'subcuenta', 'concepto'] : ['subcuenta'];
    }

    /**
     * En "ambos", la subcuenta decide: si ya existe se actualiza; si no, se crea.
     */
    public function modoDeFila(array $datos, string $modo): string
    {
        if ($modo !== Importacion::MODO_AMBOS) {
            return $modo;
        }

        return $datos['subcuenta'] !== null && isset($this->subcuentasExistentes()[$datos['subcuenta']])
            ? Importacion::MODO_ACTUALIZAR
            : Importacion::MODO_CREAR;
    }

    public function reglas(string $modo): array
    {
        $crear = $modo === Importacion::MODO_CREAR;
        $opcional = $crear ? 'required' : 'nullable';

        return [
            'tipo' => [$opcional, 'in:'.implode(',', GuardarPucRequest::TIPOS)],
            'cuenta' => [$opcional, 'string', 'max:50'],
            'subcuenta' => ['required', 'string', 'max:50'],
            'concepto' => [$opcional, 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function guardar(array $datos, string $modo): bool
    {
        return $modo === Importacion::MODO_CREAR
            ? $this->crear($datos)
            : $this->actualizar($datos);
    }

    public function ejemplos(): array
    {
        return [
            ['1', '1105', '110505', 'Caja general', ''],
            ['1', '1110', '111005', 'Bancos nacionales', 'Cuenta corriente principal'],
        ];
    }

    /**
     * @param  array<string, string|null>  $datos
     */
    private function crear(array $datos): bool
    {
        if (isset($this->subcuentasExistentes()[$datos['subcuenta']])) {
            return false;
        }

        Puc::create([
            'tipo' => $datos['tipo'],
            'cuenta' => $datos['cuenta'],
            'subcuenta' => $datos['subcuenta'],
            'concepto' => $datos['concepto'],
            'descripcion' => $datos['descripcion'],
            'concatenar_subcuenta_concepto' => Puc::textoConcatenado($datos['subcuenta'], $datos['concepto']),
        ]);
        $this->subcuentas[$datos['subcuenta']] = true;

        return true;
    }

    /**
     * @param  array<string, string|null>  $datos
     */
    private function actualizar(array $datos): bool
    {
        $puc = Puc::query()->where('subcuenta', $datos['subcuenta'])->first()
            ?? throw new FilaRechazada("No existe una cuenta con la subcuenta {$datos['subcuenta']}.");

        // Solo se cambia lo que la fila trae lleno.
        $puc->fill(array_filter(
            array_intersect_key($datos, array_flip(['tipo', 'cuenta', 'concepto', 'descripcion'])),
            fn (?string $valor): bool => $valor !== null,
        ));

        if (! $puc->isDirty()) {
            return false;
        }

        $puc->concatenar_subcuenta_concepto = Puc::textoConcatenado($puc->subcuenta, $puc->concepto);
        $puc->save();

        return true;
    }

    /**
     * @return array<string, true>
     */
    private function subcuentasExistentes(): array
    {
        return $this->subcuentas ??= Puc::query()
            ->pluck('subcuenta')
            ->mapWithKeys(fn (string $subcuenta): array => [$subcuenta => true])
            ->all();
    }
}
