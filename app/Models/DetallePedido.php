<?php

namespace App\Models;

use App\Models\NotaDocumento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pedido;
use App\Models\Producto;

class DetallePedido extends Model
{
    //
    use HasFactory, SoftDeletes;

    protected $table = 'detalle_pedidos';
    protected $fillable = [
        'pedido_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'descuento',
        'precio_con_descuento',
        'aplicar_iva',
        'iva',
        'precio_con_iva',
        'subtotal',
        'motivo_eliminacion',
        'detalle_motivo_eliminacion',
        'eliminado_por',
    ];

    /**
     * Motivos entre los que el usuario elige al quitar un producto del detalle
     * de un pedido (formulario Livewire de Pedidos).
     */
    public const MOTIVOS_ELIMINACION = [
        'Agotado',
        'Cambio de referencia o codigo',
        'Sin inventario proveedor',
        'Cliente en mora',
        'Error Comercial',
        'Error Facturacion',
        'El cliente no lo necesita',
        'Cambio por garantia',
        
    ];

    public function eliminadoPor()
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function notaDocumento()
    {
        return $this->belongsTo(NotaDocumento::class, 'nota_documento_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function getPedidoParaExportacion(): ?Pedido
    {
        if (filled($this->nota_documento_id)) {
            return $this->notaDocumento?->pedido;
        }

        return $this->pedido;
    }

    public function esDetalleNotaDocumento(): bool
    {
        return filled($this->nota_documento_id);
    }

    public function getTipoRegistroExportacion(): string
    {
        return $this->esDetalleNotaDocumento() ? 'NOTA_DOCUMENTO' : 'PEDIDO';
    }

    /**
     * Productos que se quitaron del detalle de un pedido (soft delete), con su
     * motivo/observacion/usuario/fecha. Excluye los que hoy estan activos en
     * el pedido (un producto eliminado y luego re-agregado se restaura, y los
     * duplicados que limpia SincronizarDetallesPedido no son un producto
     * quitado). Usado por PedidoFormLivewire (seccion "Productos no
     * entregados") y por el PDF de productos eliminados.
     *
     * @return \Illuminate\Support\Collection<int, DetallePedido>
     */
    public static function eliminadosDe(int $pedidoId)
    {
        $idsActivos = static::where('pedido_id', $pedidoId)->pluck('producto_id');

        return static::onlyTrashed()
            ->where('pedido_id', $pedidoId)
            ->whereNotIn('producto_id', $idsActivos)
            ->with(['producto:id,concatenar_codigo_nombre', 'eliminadoPor:id,name'])
            ->orderByDesc('deleted_at')
            ->get();
    }

    /*public function getProductoDatos()
    {
        return PedidoCalculoService::calcularDatosProducto($this->producto);
    }*/

}

