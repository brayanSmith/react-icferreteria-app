<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS detalle_pedido_exportables');

        DB::statement(<<<SQL
            CREATE VIEW detalle_pedido_exportables AS
            SELECT
                'PED-' || detalle_pedidos.id AS id,
                'PEDIDO' AS tipo_registro,
                NULL AS nota_documento_id,
                NULL AS tipo_nota_documento,
                detalle_pedidos.pedido_id AS pedido_id,
                detalle_pedidos.tipo_item AS tipo_item,
                detalle_pedidos.producto_id AS producto_id,
                detalle_pedidos.cantidad AS cantidad,
                detalle_pedidos.precio_unitario AS precio_unitario,
                detalle_pedidos.precio_con_iva AS precio_con_iva,
                detalle_pedidos.aplicar_iva AS aplicar_iva,
                detalle_pedidos.iva AS iva,
                detalle_pedidos.subtotal AS subtotal,
                detalle_pedidos.created_at AS created_at,
                detalle_pedidos.updated_at AS updated_at,
                detalle_pedidos.deleted_at AS deleted_at
            FROM detalle_pedidos

            UNION ALL

            SELECT
                'NOTA-' || detalle_nota_documentos.id AS id,
                'NOTA_DOCUMENTO' AS tipo_registro,
                detalle_nota_documentos.nota_documento_id AS nota_documento_id,
                detalle_nota_documentos.tipo_nota_documento AS tipo_nota_documento,
                nota_documentos.pedido_id AS pedido_id,
                NULL AS tipo_item,
                detalle_nota_documentos.producto_id AS producto_id,
                detalle_nota_documentos.cantidad AS cantidad,
                detalle_nota_documentos.precio_unitario AS precio_unitario,
                NULL AS precio_con_iva,
                NULL AS aplicar_iva,
                NULL AS iva,
                detalle_nota_documentos.subtotal AS subtotal,
                detalle_nota_documentos.created_at AS created_at,
                detalle_nota_documentos.updated_at AS updated_at,
                detalle_nota_documentos.deleted_at AS deleted_at
            FROM detalle_nota_documentos
            INNER JOIN nota_documentos ON nota_documentos.id = detalle_nota_documentos.nota_documento_id
            WHERE nota_documentos.deleted_at IS NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS detalle_pedido_exportables');
    }
};
