<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Services\ComisionService;
use Illuminate\Database\Eloquent\Model;

class Comision extends Model
{
    use BelongsToTenant;

    //
    protected $fillable = [
        'vendedor_id',
        'periodo_inicial',
        'periodo_final',
        'estado_comision',

        'monto_venta_remisionada',
        'iva_venta_remisionada',
        'total_venta_remisionada',
        'monto_venta_electronica',
        'iva_venta_electronica',
        'total_venta_electronica',
        'monto_total_ventas',
        'porcentaje_comision_ventas',
        'total_comision_ventas',
        'monto_abonos',
        'iva_abonos',
        'total_abonos',
        'porcentaje_comision_abonos',
        'total_comision_abonos',
        'subtotal_comision',
        'descuento_comision',
        'ajuste_comision',
        'total_comision_neta',

        'monto_contado_electronico',
        'monto_contado_remisionada',
        'iva_contado_electronico',
        'total_calculado_contado',
        'porcentaje_comision_contado',
        'comision_calculada_contado',
        'monto_credito_electronico',
        'monto_credito_remisionada',
        'iva_credito_electronico',
        'total_calculado_credito',
        'porcentaje_comision_credito',
        'comision_calculada_credito',
    ];

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function detallesComisionPedidos()
    {
        return $this->hasMany(DetalleComisionPedido::class, 'comision_id');
    }

    public function detallesComisionAbonos()
    {
        return $this->hasMany(DetalleComisionAbono::class, 'comision_id');
    }

    /**
     * Obtiene pedidos para previsualización (sin guardar)
     */
    public function obtenerPedidosParaComision(): array
    {
        return app(ComisionService::class)->obtenerPedidosParaComision($this);
    }

    /**
     * Obtiene abonos para previsualización (sin guardar)
     */
    public function obtenerAbonosParaComision(): array
    {
        return app(ComisionService::class)->obtenerAbonosParaComision($this);
    }

    /**
     * Calcula totales en tiempo real (sin guardar)
     */
    /*public function calcularTotalesEnTiempoReal(
        array $detallesPedidos = null,
        array $detallesAbonos = null
    ): array {
        return app(ComisionService::class)->obtenerTotalesComision(
            $this,
            $this->iva_venta_remisionada ?? 0,
            $this->iva_venta_electronica ?? 0,
            $this->iva_abonos ?? 0,
            $this->porcentaje_comision_ventas ?? 0,
            $this->porcentaje_comision_abonos ?? 0,
            $this->descuento_comision ?? 0,
            $this->ajuste_comision ?? 0,
            $detallesPedidos,
            $detallesAbonos
        );
    }*/

    /**
     * Recalcula los totales de la comisión
     */
    public function recalcularTotales(): void
    {
        app(ComisionService::class)->calcularTotalesComision(
            $this,
            $this->iva_venta_remisionada ?? 0,
            $this->iva_venta_electronica ?? 0,
            $this->iva_abonos ?? 0,
            $this->porcentaje_comision_ventas ?? 0,
            $this->porcentaje_comision_abonos ?? 0,
            $this->descuento_comision ?? 0,
            $this->ajuste_comision ?? 0
        );
    }
}
