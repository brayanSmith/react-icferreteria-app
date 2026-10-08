<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('detalle_pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->enum('tipo_item', ['PRODUCTO', 'GASTO'])->default('PRODUCTO');
            $table->foreignId('producto_id')->constrained('productos');

            $table->integer('cantidad');
            $table->decimal('precio_unitario', 12, 2); // snapshot del precio
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('precio_con_descuento', 12, 2)->default(0);
            $table->decimal('precio_con_iva', 12, 2);
            $table->boolean('aplicar_iva')->default(true);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->text('motivo_eliminacion')->nullable();
            $table->text('detalle_motivo_eliminacion')->nullable();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_pedidos');
    }
};
