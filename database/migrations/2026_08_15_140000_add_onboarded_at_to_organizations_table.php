<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SaaS Phase 3 — onboarding. A tenant is "onboarded" once it has finished (or
 * skipped) the welcome checklist. Null = still to be guided through setup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->timestamp('onboarded_at')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('onboarded_at');
        });
    }
};
