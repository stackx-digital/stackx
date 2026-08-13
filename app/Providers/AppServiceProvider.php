<?php

namespace App\Providers;

use App\Services\Ai\AiManager;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Provider-agnostic AI layer (§5). Resolve via the Ai facade or by
        // type-hinting AiManager; swap providers with AI_PROVIDER in .env.
        $this->app->singleton(AiManager::class, fn ($app) => new AiManager($app));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
