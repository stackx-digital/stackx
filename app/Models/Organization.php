<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    protected $fillable = ['name', 'slug', 'onboarded_at'];

    protected function casts(): array
    {
        return [
            'onboarded_at' => 'datetime',
        ];
    }

    /** Members of this tenant. Solo model today (1 user), but modelled as many. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** This tenant's BYO credentials + provider preferences (Phase 2). */
    public function settings(): HasOne
    {
        return $this->hasOne(OrganizationSetting::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function adAccounts(): HasMany
    {
        return $this->hasMany(AdAccount::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
}
