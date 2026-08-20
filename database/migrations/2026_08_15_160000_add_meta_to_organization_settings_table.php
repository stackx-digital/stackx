<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live Meta Marketing sync (BYO). A tenant stores their own System User token
 * (encrypted) and the ad account id to pull creative performance from. The
 * token is a secret; the account id is a plain identifier (act_...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->text('meta_system_token')->nullable()->after('meta_ad_library_token'); // encrypted
            $table->string('meta_ad_account_id')->nullable()->after('meta_system_token');
        });
    }

    public function down(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->dropColumn(['meta_system_token', 'meta_ad_account_id']);
        });
    }
};
