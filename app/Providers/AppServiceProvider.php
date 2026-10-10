<?php

namespace App\Providers;

use App\Models\StockInicial;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureMorphMap();
    }

    /**
     * Nombres cortos y estables para los documentos que originan movimientos de
     * inventario. Así la base guarda "stock_inicial" y no el nombre de la clase,
     * que podría cambiar de lugar. Se agregan aquí a medida que otros documentos
     * (pedidos, compras...) empiecen a mover stock.
     */
    protected function configureMorphMap(): void
    {
        Relation::morphMap([
            'stock_inicial' => StockInicial::class,
        ]);
    }

    /**
     * El super administrador y el administrador de un tenant tienen acceso
     * total en su ámbito: no dependen de ningún rol, así que nadie puede
     * quedarse bloqueado por borrar o editar un rol.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(fn (User $user): ?bool => $user->es_super_admin || $user->es_admin ? true : null);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
