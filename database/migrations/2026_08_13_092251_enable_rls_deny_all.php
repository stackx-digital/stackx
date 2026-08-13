<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Locks the domain tables at the database layer (§4 defense in depth). We run
 * on Laravel Auth, so org-scoping is enforced in the app (Eloquent global
 * scope). This migration adds a belt: enabling RLS with NO policies means any
 * non-owner role — Supabase's anon / authenticated (PostgREST) roles — is
 * denied all access. Laravel connects as the privileged owner and is
 * unaffected. Postgres-only; a no-op on sqlite (local/testing).
 */
return new class extends Migration
{
    private array $tables = [
        'organizations',
        'brands',
        'ad_accounts',
        'ads',
        'ad_metrics',
        'ad_scores',
        'ad_tags',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            if (Schema::hasTable($table)) {
                // ENABLE (not FORCE): the owning role Laravel connects as keeps
                // full access; non-owner API roles (anon/authenticated) are
                // denied because no policies are defined.
                DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            }
        }
    }
};
