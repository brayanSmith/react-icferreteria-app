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
        Schema::create('comisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendedor_id')->constrained('users')->onDelete('cascade');
            $table->date('periodo_inicial');
            $table->date('periodo_final');
            $table->enum('estado_comision', ['PENDIENTE', 'PAGADA', 'RECHAZADA'])->default('PENDIENTE');


            $table->decimal('monto_contado_electronico', 15, 2)->default(0);
            $table->decimal('monto_contado_remisionada', 15, 2)->default(0);
            $table->decimal('iva_contado_electronico', 5, 2)->default(0);
            $table->decimal('total_calculado_contado', 15, 2)->default(0);
            $table->decimal('porcentaje_comision_contado', 5, 2)->default(0);
            $table->decimal('comision_calculada_contado', 15, 2)->default(0);

            $table->decimal('monto_credito_electronico', 15, 2)->default(0);
            $table->decimal('monto_credito_remisionada', 15, 2)->default(0);
            $table->decimal('iva_credito_electronico', 5, 2)->default(0);
            $table->decimal('total_calculado_credito', 15, 2)->default(0);
            $table->decimal('porcentaje_comision_credito', 5, 2)->default(0);
            $table->decimal('comision_calculada_credito', 15, 2)->default(0);


            $table->decimal('monto_venta_remisionada', 15, 2)->default(0);
            $table->decimal('iva_venta_remisionada', 5, 2)->default(0);
            $table->decimal('total_venta_remisionada', 15, 2)->default(0);
            $table->decimal('monto_venta_electronica', 15, 2)->default(0);
            $table->decimal('iva_venta_electronica', 5, 2)->default(0);
            $table->decimal('total_venta_electronica', 15, 2)->default(0);
            $table->decimal('monto_total_ventas', 15, 2)->default(0);
            $table->decimal('porcentaje_comision_ventas', 5, 2)->default(0);
            $table->decimal('total_comision_ventas', 15, 2)->default(0);
            $table->decimal('monto_abonos', 15, 2)->default(0);
            $table->decimal('iva_abonos', 15, 2)->default(0);
            $table->decimal('total_abonos', 15, 2)->default(0);
            $table->decimal('porcentaje_comision_abonos', 5, 2)->default(0);
            $table->decimal('total_comision_abonos', 15, 2)->default(0);
            $table->decimal('subtotal_comision', 15, 2)->default(0);
            $table->decimal('descuento_comision', 15, 2)->default(0);
            $table->decimal('ajuste_comision', 15, 2)->default(0);
            $table->decimal('total_comision_neta', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comisions');
    }
};
