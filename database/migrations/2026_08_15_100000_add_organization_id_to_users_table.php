<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SaaS tenancy: bind every user to an organization. On self-serve signup we
 * create one org per account (solo model — 1 user = 1 org), and this column is
 * how CurrentOrganization scopes all of that tenant's data. Nullable so the
 * column can be added to a table that already has rows; registration always
 * sets it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
