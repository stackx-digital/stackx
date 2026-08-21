<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Slack integration (weekly report summary + alert digest) was removed —
 * data now flows in via the inbound push API (Settings → API access) instead
 * of the app calling out to third-party webhooks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->dropColumn('slack_webhook_url');
        });
    }

    public function down(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->text('slack_webhook_url')->nullable();
        });
    }
};
