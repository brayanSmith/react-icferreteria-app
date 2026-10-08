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
        Schema::create('medidas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_medida')->unique();
            $table->enum('tipo_medida', ['LONGITUD', 'PESO', 'VOLUMEN', 'CANTIDAD'])->default('CANTIDAD');
            $table->string('descripcion_medida')->nullable();
            $table->timestamps();
        });

        Schema::create('bodegas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_bodega')->unique();
            $table->string('ubicacion_bodega')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_categoria')->unique();
            $table->timestamps();
        });

        Schema::create('sub_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_sub_categoria')->unique();
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->enum('categoria_producto', ['MATERIA_PRIMA', 'PRODUCTO_TERMINADO', 'OTRO', 'DESCUENTO', 'GASTOS'])->default('PRODUCTO_TERMINADO');
            $table->string('codigo_producto')->unique();
            $table->string('nombre_producto');
            $table->string('descripcion_producto')->nullable();
            $table->decimal('costo_producto', 10, 2)->default(0);
            $table->decimal('valor_detal_producto', 10, 2)->default(0);
            $table->decimal('valor_mayorista_producto', 10, 2)->default(0);
            $table->decimal('valor_ferretero_producto', 10, 2)->default(0);
            $table->decimal('valor_especial_producto', 10, 2)->default(0);
            $table->string('imagen_producto')->nullable();
            $table->foreignId('medida_id')->constrained('medidas')->onDelete('cascade')->nullable();
            $table->foreignId('bodega_id')->constrained('bodegas')->onDelete('cascade')->nullable();
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade')->nullable();
            $table->foreignId('sub_categoria_id')->constrained('sub_categorias')->onDelete('cascade')->nullable();
            $table->integer('stock_inicial')->default(0)->nullable();
            $table->integer('entradas')->default(0)->nullable();
            $table->integer('salidas')->default(0)->nullable();
            $table->boolean('activo')->default(true)->nullable();
            $table->string('tipo_producto')->nullable();
            $table->decimal('peso_producto', 8, 2)->nullable();
            $table->string('ubicacion_producto')->nullable();
            $table->integer('alerta_producto')->default(0);
            $table->string('empaquetado_externo')->nullable();
            $table->string('empaquetado_interno')->nullable();
            $table->string('referencia_producto')->nullable();
            $table->string('codigo_cliente')->nullable();
            $table->enum('volumen_producto', ['EXTRA_GRANDE', 'GRANDE', 'MEDIANO', 'PEQUEÑO', 'EXTRA_PEQUEÑO'])->nullable();
            $table->float('iva_producto')->default(0)->nullable();
            $table->enum('tipo_compra',['NACIONAL','IMPORTADO'])->nullable();
            $table->string('concatenar_codigo_nombre')->nullable();
            //Nuevos Campos
            $table->string('codigo_barra')->nullable();
            $table->string('marca')->nullable();
            $table->text('detalles_producto')->nullable();
            $table->text('codigo_importado')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
        Schema::dropIfExists('sub_categorias');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('bodegas');
        Schema::dropIfExists('medidas');
    }
};
