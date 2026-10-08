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
        Schema::create('nota_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade');
            $table->enum('tipo_nota', ['credito', 'debito']);
            $table->enum('motivo', ['DEVOLUCION', 'DESCUENTO', 'OTRO']);
            $table->text('observaciones')->nullable();
            $table->decimal('monto_total', 15, 2)->default(0);
            $table->decimal('descuento_total', 15, 2)->default(0);
            $table->string('imagen')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nota_documentos');
    }
};
