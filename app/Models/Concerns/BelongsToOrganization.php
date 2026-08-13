<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Support\CurrentOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App-layer org scoping (§4). Adds a global scope so every query is confined
 * to the current organization, and auto-fills organization_id on create. This
 * is our isolation boundary since we run on Laravel Auth (Supabase-style RLS
 * keyed on auth.uid() doesn't apply — see the enable_rls_deny_all migration
 * for the DB-layer lock that complements this).
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            $orgId = app(CurrentOrganization::class)->id();

            if ($orgId !== null) {
                $builder->where($builder->getModel()->getTable().'.organization_id', $orgId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->organization_id)) {
                $model->organization_id = app(CurrentOrganization::class)->id();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
