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
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('color_primario_oscuro', 7)->nullable()->after('color_primario');
            $table->string('color_menu', 7)->nullable()->after('color_primario_oscuro');
            $table->string('color_menu_oscuro', 7)->nullable()->after('color_menu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['color_primario_oscuro', 'color_menu', 'color_menu_oscuro']);
        });
    }
};
