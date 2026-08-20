<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI strategic reasoning (§3, M5) layered on top of the deterministic action.
 * Stored separately from action/action_reason (which stay deterministic) and
 * always labelled as inferred in the UI. ai_recommendation_by records the
 * provider:model so provenance is auditable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_scores', function (Blueprint $table) {
            $table->text('ai_recommendation')->nullable()->after('action_reason');
            $table->string('ai_recommendation_by')->nullable()->after('ai_recommendation');
        });
    }

    public function down(): void
    {
        Schema::table('ad_scores', function (Blueprint $table) {
            $table->dropColumn(['ai_recommendation', 'ai_recommendation_by']);
        });
    }
};
