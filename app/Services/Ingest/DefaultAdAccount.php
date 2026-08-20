<?php

namespace App\Services\Ingest;

use App\Models\AdAccount;

/**
 * Resolves an ad account to import into. M2 has no account-management UI, so
 * CSV imports land in a single default account per org ("CSV Import"). When
 * the Meta API sync arrives, real accounts replace this.
 */
class DefaultAdAccount
{
    public function resolve(): AdAccount
    {
        return AdAccount::firstOrCreate(
            ['name' => 'CSV Import'],
            ['currency' => 'MYR'],
        );
    }
}
