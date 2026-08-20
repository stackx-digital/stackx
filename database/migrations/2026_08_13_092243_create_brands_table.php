<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Brands — the clients we run ads for (§4). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('meta_ad_account_id')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
