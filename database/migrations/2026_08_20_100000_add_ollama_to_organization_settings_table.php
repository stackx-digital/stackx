<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ollama Cloud as a third AI text-layer provider (BYO, alongside Anthropic and
 * OpenAI). Key encrypted; model is a plain preference.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->text('ollama_api_key')->nullable()->after('openai_model'); // encrypted
            $table->string('ollama_model')->nullable()->after('ollama_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->dropColumn(['ollama_api_key', 'ollama_model']);
        });
    }
};
