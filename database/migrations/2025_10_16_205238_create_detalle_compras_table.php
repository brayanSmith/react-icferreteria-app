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
        Schema::create('detalle_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            //$table->foreignId('producto_id')->constrained('productos');
            $table->unsignedBigInteger('item_id');
            $table->string('descripcion_item')->nullable()  ;
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario', 12, 2); // snapshot del precio
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('precio_con_descuento', 12, 2)->default(0);
            $table->decimal('precio_ferretero', 12, 2)->default(0); // Nuevo Campo
            $table->decimal('precio_mayorista', 12, 2)->default(0); //Nuevo Campo
            $table->decimal('precio_detal', 12, 2)->default(0);
            $table->decimal('precio_especial', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('precio_con_iva', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('tipo_item');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_compras');
    }
};
