<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SaaS Phase 2 — per-tenant "bring your own keys". Each organization stores its
 * own AI / embedding / Meta / Slack credentials. Secret columns hold Laravel-
 * encrypted ciphertext (see OrganizationSetting casts), so they're `text` and
 * never readable at the DB layer. Non-secret prefs (provider/model choice) are
 * plain. One row per org (1:1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();

            // AI text layer (config/ai.php).
            $table->string('ai_provider')->nullable();        // anthropic | openai
            $table->text('anthropic_api_key')->nullable();    // encrypted
            $table->string('anthropic_model')->nullable();
            $table->text('openai_api_key')->nullable();        // encrypted
            $table->string('openai_model')->nullable();

            // Embeddings (config/embedding.php). OpenAI reuses openai_api_key.
            $table->string('embedding_provider')->nullable(); // openai | voyage
            $table->text('voyage_api_key')->nullable();        // encrypted

            // Brand Spy (config/ad_library.php).
            $table->text('meta_ad_library_token')->nullable(); // encrypted

            // Reports (config/services.php slack).
            $table->text('slack_webhook_url')->nullable();     // encrypted

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_settings');
    }
};
