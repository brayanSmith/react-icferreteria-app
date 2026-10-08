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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('numero');
            $table->unique(['tenant_id', 'numero']);
            $table->string('tipo_documento');
            $table->string('numero_documento');
            $table->unique(['tenant_id', 'numero_documento']);
            $table->string('razon_social');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('email')->nullable();
            $table->unique(['tenant_id', 'email']);
            $table->string('representante_legal')->nullable();
            $table->boolean('activo')->default(true);
            $table->string('novedad')->nullable();
            $table->foreignId('ruta_id')->nullable()->constrained('rutas')->cascadeOnDelete();
            $table->foreignId('comercial_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->enum('tipo_cliente', ['ELECTRONICO', 'REMISIONADO'])->default('ELECTRONICO');
            $table->string('rut_imagen')->nullable();
            $table->enum('retenedor_fuente', ['SI', 'NO'])->default('NO');
            $table->integer('cuenta_total_pedidos_en_cartera')->default(0)->nullable();
            $table->decimal('saldo_total_pedidos_en_cartera', 12, 2)->default(0)->nullable();
            $table->decimal('saldo_total_pedidos_vencidos', 12, 2)->default(0)->nullable();
            $table->decimal('limite_credito', 12, 2)->default(0)->nullable();
            // Nuevos Campos
            $table->string('categoria')->nullable();
            $table->string('sector')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
