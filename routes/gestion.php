<?php

use App\Http\Controllers\Gestion\EmpresaController;
use App\Http\Controllers\Gestion\RoleController;
use App\Http\Controllers\Gestion\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'has-tenant'])
    ->prefix('gestion')
    ->name('gestion.')
    ->group(function () {
        Route::singleton('empresa', EmpresaController::class)->only(['edit', 'update']);

        Route::resource('roles', RoleController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('usuarios', UserController::class)
            ->parameters(['usuarios' => 'usuario'])
            ->only(['index', 'store', 'update', 'destroy']);
    });
