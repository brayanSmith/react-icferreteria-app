<?php

use App\Http\Controllers\Admin\RestoredTenantController;
use App\Http\Controllers\Admin\TenantAdminController;
use App\Http\Controllers\Admin\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'super-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('tenants', TenantController::class)->except('show');

        Route::resource('tenants.admins', TenantAdminController::class)
            ->only(['store', 'update', 'destroy'])
            ->scoped();

        Route::post('restored-tenants/{tenant}', [RestoredTenantController::class, 'store'])
            ->withTrashed()
            ->name('restored-tenants.store');
    });
