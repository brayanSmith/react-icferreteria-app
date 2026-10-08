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
        Schema::create('detalle_comision_abonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comision_id')->constrained('comisions')->onDelete('cascade');
            $table->foreignId('abono_id')->constrained('abonos')->onDelete('cascade');            
            $table->decimal('monto_abono', 15, 2)->default(0);
            $table->date('fecha_abono');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_comision_abonos');
    }
};
