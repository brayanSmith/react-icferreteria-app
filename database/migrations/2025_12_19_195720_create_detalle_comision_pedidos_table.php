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
        Schema::create('detalle_comision_pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comision_id')->constrained('comisions')->onDelete('cascade');
            $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade');
            $table->decimal('monto_venta', 15, 2)->default(0);   
            $table->string('tipo_venta')->nullable();
            $table->date('fecha_venta');
            $table->date('fecha_actualizacion_venta')->nullable();         
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_comision_pedidos');
    }
};
