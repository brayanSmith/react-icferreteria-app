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
        Schema::create('importaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Módulo al que pertenece el archivo (categorias, productos...).
            $table->string('modulo', 50);
            $table->string('nombre_original');
            // Ruta del archivo subido; se borra al terminar de procesarlo.
            $table->string('ruta')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->string('delimitador', 5)->nullable();
            // crear (registros nuevos) o actualizar (registros existentes, identificados por su ID).
            $table->string('modo', 15)->default('crear');
            // Encabezados del archivo y primeras filas, para configurar la importación.
            $table->json('encabezados')->nullable();
            $table->json('muestra')->nullable();
            // Qué columna del archivo (posición) alimenta cada columna del sistema.
            $table->json('mapeo')->nullable();
            $table->unsignedInteger('total_filas')->default(0);
            $table->unsignedInteger('filas_procesadas')->default(0);
            $table->unsignedInteger('filas_creadas')->default(0);
            $table->unsignedInteger('filas_actualizadas')->default(0);
            $table->unsignedInteger('filas_omitidas')->default(0);
            $table->unsignedInteger('filas_con_error')->default(0);
            // Detalle de las filas rechazadas: [{fila, mensaje}] (con tope).
            $table->json('errores')->nullable();
            $table->string('mensaje')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'modulo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importaciones');
    }
};
