<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creative library boards (swipe files). Org-scoped collections the team saves
 * winning/inspiring ads into. RLS deny-all on Postgres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE boards ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
