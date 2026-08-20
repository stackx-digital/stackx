<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organizations. Schema is multi-org-ready (§4) but we seed a single STACKx
 * org and build no org-management UI. Every domain row carries org_id so
 * app-layer scoping (Eloquent global scope) can isolate data cleanly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
