<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ad extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'ad_account_id',
        'meta_ad_id',
        'name',
        'status',
        'thumbnail_url',
    ];

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(AdMetric::class);
    }

    public function score(): HasOne
    {
        return $this->hasOne(AdScore::class);
    }

    public function tags(): HasOne
    {
        return $this->hasOne(AdTag::class);
    }
}
