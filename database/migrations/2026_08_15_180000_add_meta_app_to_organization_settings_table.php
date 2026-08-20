<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Meta app credentials for server-side Marketing API calls. The app secret lets
 * us send an appsecret_proof — Meta's way of proving a request genuinely comes
 * from the app — which is required/expected for server calls and resolves
 * "(#200) Provide valid app ID" for apps in Development mode. Secret encrypted;
 * app id is a plain identifier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->string('meta_app_id')->nullable()->after('meta_ad_account_id');
            $table->text('meta_app_secret')->nullable()->after('meta_app_id'); // encrypted
        });
    }

    public function down(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->dropColumn(['meta_app_id', 'meta_app_secret']);
        });
    }
};
