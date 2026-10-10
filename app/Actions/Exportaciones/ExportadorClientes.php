<?php

namespace App\Actions\Exportaciones;

use App\Enums\Modulo;
use App\Models\Cliente;

/**
 * Exporta los clientes con las mismas columnas que acepta la importación, así
 * que el archivo se puede editar y volver a importar para actualizar clientes
 * (el número de documento los identifica).
 */
class ExportadorClientes extends Exportador
{
    private const TIPOS_DOCUMENTO = [
        'NIT' => 'NIT',
        'CC' => 'Cédula de ciudadanía',
        'CE' => 'Cédula de extranjería',
        'TI' => 'Tarjeta de identidad',
        'PASAPORTE' => 'Pasaporte',
        'OTRO' => 'Otro',
    ];

    private const TIPOS_CLIENTE = [
        'ELECTRONICO' => 'Electrónico',
        'REMISIONADO' => 'Remisionado',
    ];

    public function modulo(): Modulo
    {
        return Modulo::Clientes;
    }

    public function titulo(): string
    {
        return 'Clientes';
    }

    public function encabezados(): array
    {
        return [
            'Tipo de documento', 'Número de documento', 'Razón social', 'Representante legal', 'Correo', 'Teléfono',
            'Ciudad', 'Dirección', 'Ruta', 'Comercial', 'Categoría', 'Sector', 'Tipo de cliente',
            'Retenedor de fuente', 'Límite de crédito', 'Activo', 'Novedad',
        ];
    }

    public function filas(): iterable
    {
        $clientes = Cliente::query()
            ->with(['ruta:id,ruta', 'comercial:id,name'])
            ->orderBy('razon_social')
            ->lazy(500);

        foreach ($clientes as $cliente) {
            yield [
                self::TIPOS_DOCUMENTO[$cliente->tipo_documento] ?? $cliente->tipo_documento,
                $cliente->numero_documento,
                $cliente->razon_social,
                $cliente->representante_legal ?? '',
                $cliente->email ?? '',
                $cliente->telefono ?? '',
                $cliente->ciudad ?? '',
                $cliente->direccion ?? '',
                $cliente->ruta?->ruta ?? '',
                $cliente->comercial?->name ?? '',
                $cliente->categoria ?? '',
                $cliente->sector ?? '',
                self::TIPOS_CLIENTE[$cliente->tipo_cliente] ?? $cliente->tipo_cliente,
                $cliente->retenedor_fuente === 'SI' ? 'Sí' : 'No',
                $cliente->limite_credito,
                $cliente->activo ? 'Sí' : 'No',
                $cliente->novedad ?? '',
            ];
        }
    }
}
