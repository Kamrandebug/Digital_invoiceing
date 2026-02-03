<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessing;
use App\Models\Tenant;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Queue Tenant Context Injection
        // When a job is processed, we need to ensure the tenant context is restored.
        // However, standard Laravel jobs serialize the model. If we pass the Tenant model, it works.
        // But if the job is generic, we might need to listen to events.
        // For now, let's leave this empty as the basic requirement is handled by Middleware.
        // We can add Queue listeners here if needed for advanced context propagation.
    }
}
