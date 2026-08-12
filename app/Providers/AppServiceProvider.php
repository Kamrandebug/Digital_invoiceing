<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
        // Landlord database migrations
        $this->loadMigrationsFrom(database_path('migrations/landlord'));

        // Register custom tenant permissions helper
        Blade::if('tenantCan', function (string $module, string $action) {
            $permissions = session('selected_tenant.permissions', []);
            return (int)($permissions[$module][$action] ?? 0) === 1;
        });
    }
}
