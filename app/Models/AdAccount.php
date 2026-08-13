<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdAccount extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'brand_id',
        'name',
        'meta_ad_account_id',
        'currency',
        'token_ref',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
}
