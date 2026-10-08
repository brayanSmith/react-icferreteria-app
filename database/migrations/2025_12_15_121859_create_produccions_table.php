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
        Schema::create('produccions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formula_id')->constrained('formulas')->onDelete('cascade');
            $table->foreignId('bodega_id')->constrained('bodegas')->onDelete('cascade');
            $table->integer('cantidad');
            $table->string('lote');
            $table->decimal('ph', 8, 2);
            $table->integer('biscocidad');
            $table->integer('homogeneidad');
            $table->date('fecha_produccion');
            $table->date('fecha_caducidad');
            $table->text('observaciones')->nullable();
            $table->foreignId('responsable_lote_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('responsable_cc_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produccions');
    }
};
