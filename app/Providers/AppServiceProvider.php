<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\CondicionSalud;
use App\Models\Seguridad\PruebaAlcoholemia;
use App\Observers\Seguridad\ColaboradorExamenObserver;
use App\Observers\Seguridad\CondicionSaludObserver;
use App\Observers\Seguridad\PruebaAlcoholemiaObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Administrador has unrestricted access to every ability, including
        // ones added in the future, without needing explicit permissions.
        Gate::before(fn ($user, string $ability) => $user->hasRole(Role::Administrador->value) ? true : null);

        PruebaAlcoholemia::observe(PruebaAlcoholemiaObserver::class);
        CondicionSalud::observe(CondicionSaludObserver::class);
        Colaborador::observe(ColaboradorExamenObserver::class);
    }
}
