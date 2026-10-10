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
        Schema::create('stock_bodegas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('bodega_id')->constrained('bodegas');
            $table->foreignId('producto_id')->constrained('productos');
            // Un producto tiene un único registro por bodega dentro de cada tenant.
            $table->unique(['tenant_id', 'producto_id', 'bodega_id']);
            $table->decimal('entradas', 12, 2)->default(0);
            $table->decimal('salidas', 12, 2)->default(0);
            // Siempre es entradas - salidas; lo calcula el modelo al guardar.
            $table->decimal('stock', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_bodegas');
    }
};
