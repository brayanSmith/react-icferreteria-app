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
        Schema::create('detalle_produccion_salidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produccion_id')->constrained('produccions')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->decimal('cantidad_producto', 10, 2)->default(0);
            $table->decimal('costo_producto', 15, 2)->default(0);
            $table->decimal('total_costo', 15, 2)->default(0);
            $table->date('fecha_produccion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_produccion_salidas');
    }
};
