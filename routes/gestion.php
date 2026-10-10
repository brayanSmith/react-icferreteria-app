<?php

use App\Http\Controllers\Gestion\BodegaController;
use App\Http\Controllers\Gestion\CategoriaController;
use App\Http\Controllers\Gestion\ClienteController;
use App\Http\Controllers\Gestion\EmpresaController;
use App\Http\Controllers\Gestion\ExportacionController;
use App\Http\Controllers\Gestion\FormulaController;
use App\Http\Controllers\Gestion\ImportacionController;
use App\Http\Controllers\Gestion\MedidaController;
use App\Http\Controllers\Gestion\MovimientoStockController;
use App\Http\Controllers\Gestion\ProductoController;
use App\Http\Controllers\Gestion\ProveedorController;
use App\Http\Controllers\Gestion\PucController;
use App\Http\Controllers\Gestion\RoleController;
use App\Http\Controllers\Gestion\RutaController;
use App\Http\Controllers\Gestion\StockBodegaController;
use App\Http\Controllers\Gestion\StockInicialController;
use App\Http\Controllers\Gestion\SubCategoriaController;
use App\Http\Controllers\Gestion\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'has-tenant'])
    ->prefix('gestion')
    ->name('gestion.')
    ->group(function () {
        Route::singleton('empresa', EmpresaController::class)->only(['edit', 'update']);

        // --- Catálogos del tenant ---------------------------------------------
        Route::resource('pucs', PucController::class)
            ->parameters(['pucs' => 'puc'])
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('medidas', MedidaController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('rutas', RutaController::class)->only(['index', 'store', 'update', 'destroy']);

        // Categorías y subcategorías comparten pantalla; las subcategorías no tienen listado propio.
        Route::resource('categorias', CategoriaController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('categorias.subcategorias', SubCategoriaController::class)
            ->parameters(['subcategorias' => 'sub_categoria'])
            ->only(['store', 'update', 'destroy'])
            ->scoped();

        // Bodegas, proveedores y productos se eliminan de forma lógica y se pueden restaurar.
        Route::resource('bodegas', BodegaController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('bodegas/{bodega}/restore', [BodegaController::class, 'restore'])
            ->withTrashed()->name('bodegas.restore');

        Route::resource('formulas', FormulaController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('clientes', ClienteController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('clientes/{cliente}/restore', [ClienteController::class, 'restore'])
            ->withTrashed()->name('clientes.restore');

        Route::resource('proveedores', ProveedorController::class)
            ->parameters(['proveedores' => 'proveedor'])
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('proveedores/{proveedor}/restore', [ProveedorController::class, 'restore'])
            ->withTrashed()->name('proveedores.restore');

        Route::resource('stock-inicial', StockInicialController::class)
            ->parameters(['stock-inicial' => 'stock_inicial'])
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('stock-inicial/{stock_inicial}/restore', [StockInicialController::class, 'restore'])
            ->withTrashed()->name('stock-inicial.restore');

        // Existencias por bodega: se mueven con ingresos y salidas, no se editan ni se eliminan.
        Route::resource('stock-bodegas', StockBodegaController::class)
            ->parameters(['stock-bodegas' => 'stock_bodega'])
            ->only(['index', 'show', 'store']);
        Route::resource('stock-bodegas.movimientos', MovimientoStockController::class)
            ->parameters(['stock-bodegas' => 'stock_bodega', 'movimientos' => 'movimiento'])
            ->only(['update', 'destroy']);

        Route::resource('productos', ProductoController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('productos/{producto}/restore', [ProductoController::class, 'restore'])
            ->withTrashed()->name('productos.restore');
        // ----------------------------------------------------------------------

        // Importar (CSV, en segundo plano) y exportar (CSV, TXT, XLSX, PDF) los módulos registrados.
        Route::get('importar/{modulo}/plantilla', [ImportacionController::class, 'plantilla'])->name('importar.plantilla');
        Route::post('importar/{modulo}', [ImportacionController::class, 'store'])->name('importar.store');
        Route::post('importaciones/{importacion}/confirmar', [ImportacionController::class, 'confirmar'])->name('importar.confirmar');
        Route::delete('importaciones/{importacion}', [ImportacionController::class, 'destroy'])->name('importar.destroy');
        Route::get('exportar/{modulo}/{formato}', ExportacionController::class)->name('exportar');

        Route::resource('roles', RoleController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('usuarios', UserController::class)
            ->parameters(['usuarios' => 'usuario'])
            ->only(['index', 'store', 'update', 'destroy']);
    });
