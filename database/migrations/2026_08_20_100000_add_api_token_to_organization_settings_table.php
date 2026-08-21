<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inbound API access for machine-to-machine data pushes (e.g. an n8n workflow
 * that pulls Meta data on its own schedule and uploads it here, instead of the
 * app pulling from Meta directly). Only a SHA-256 hash of the token is stored —
 * the plaintext is shown once at generation time and never persisted, so a
 * database leak can't hand out working tokens (same principle as Sanctum's
 * personal access tokens, without adding that whole package for one token).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->string('api_token_hash')->nullable()->unique()->after('slack_webhook_url');
        });
    }

    public function down(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->dropColumn('api_token_hash');
        });
    }
};
