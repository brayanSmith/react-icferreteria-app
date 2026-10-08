<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique()->nullable();
            $table->string('fe')->nullable();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->dateTime('fecha')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('dias_plazo_vencimiento')->default(30);
            $table->date('fecha_vencimiento')->nullable();
            $table->string('ciudad')->nullable();
            $table->enum('estado', ['PENDIENTE', 'FACTURADO', 'ANULADO', 'EN_RUTA', 'ENTREGADO', 'DEVUELTO'])->default(value: 'PENDIENTE');
            $table->boolean('stock_retirado')->default(false);
            $table->string('offline_uuid')->nullable()->unique();
            $table->string('referencia')->nullable();
            $table->boolean('en_cartera')->default(false);
            $table->enum('metodo_pago', ['CREDITO', 'CONTADO'])->default('CREDITO');
            //Modificacion
            $table->enum('tipo_precio', ['FERRETERO','MAYORISTA', 'DETAL', 'ESPECIAL'])->default('FERRETERO');
            //Fin Modificacion
            $table->enum('tipo_venta', ['ELECTRONICA','REMISIONADA'])->default('REMISIONADA');
            $table->enum('estado_pago', allowed: ['EN_CARTERA', 'SALDADO'])->default('EN_CARTERA');
            $table->enum('estado_cartera', ['CARTERA_AL_DIA', 'CARTERA_VENCIDA', 'CARTERA_PAGADA', 'NO_APLICA'])->nullable();
            $table->enum('estado_venta', ['COTIZACION', 'VENTA'])->default('VENTA');
            $table->enum('canal_origen', ['POS', 'TIENDA_ONLINE', 'ADMIN'])->default('POS');

            $table->enum('estado_vencimiento', ['AL_DIA', 'VENCIDO'])->default('AL_DIA');

            $table->foreignId('bodega_id')->constrained('bodegas')->nullable()->default(1);
            $table->text('primer_comentario')->nullable();
            $table->text('segundo_comentario')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('abono', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0)->nullable();
            $table->decimal('retefuente', 12, 2)->default(0)->nullable();
            //Nuevo Campo
            $table->decimal('reteica', 12, 2)->default(0)->nullable();
            //Fin Nuevo Campo
            $table->decimal('flete', 12, 2)->default(0)->nullable();
            $table->decimal('total_nota_credito', 12, 2)->default(0);
            $table->decimal('total_nota_debito', 12, 2)->default(0);

            $table->decimal('total_a_pagar', 12, 2)->default(0);
            $table->decimal('saldo_pendiente', 12, 2)->default(0);
            $table->integer('contador_impresiones')->default(0);
            $table->boolean('impresa')->default(false);
            $table->foreignId('user_id')->constrained('users')->nullable();
            $table->foreignId('alistador_id')->nullable()->constrained('users');
            $table->foreignId('empacador_id')->nullable()->constrained('users');
            $table->integer('iva')->nullable()->default(0);
            $table->string('imagen_recibido')->nullable();
            $table->string('comentario_entrega')->nullable();
            $table->string('motivo_devolucion')->nullable();
            $table->datetime('fecha_ultimo_abono')->nullable();
            $table->double('dias_plazo_cartera')->default(30);
            $table->timestamps();
            $table->softDeletes();
            $table->text('motivo_eliminacion')->nullable();
            $table->text('detalle_motivo_eliminacion')->nullable();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
