<?php

namespace App\Providers;

use App\Services\Ai\AiManager;
use App\Services\Embedding\EmbeddingManager;
use App\Support\CurrentOrganization;
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

        // Provider-agnostic embeddings (P3) — swap via EMBEDDING_PROVIDER.
        $this->app->singleton(EmbeddingManager::class, fn ($app) => new EmbeddingManager($app));

        // Resolves the current org for app-layer scoping (§4).
        $this->app->singleton(CurrentOrganization::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
