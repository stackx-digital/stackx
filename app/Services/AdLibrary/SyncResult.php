<?php

namespace App\Services\AdLibrary;

/** Summary of a competitor sync run. */
class SyncResult
{
    public int $created = 0;

    public int $updated = 0;

    public int $deactivated = 0;

    public int $fetched = 0;
}
