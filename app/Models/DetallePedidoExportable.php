<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetallePedidoExportable extends Model
{
    use SoftDeletes;

    protected $table = 'detalle_pedido_exportables';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

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
        return $this->pedido;
    }

    public function esDetalleNotaDocumento(): bool
    {
        return $this->tipo_registro === 'NOTA_DOCUMENTO';
    }

    public function getTipoRegistroExportacion(): string
    {
        return $this->tipo_registro ?? 'PEDIDO';
    }
}
