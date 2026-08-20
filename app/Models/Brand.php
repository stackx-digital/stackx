<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'name', 'meta_ad_account_id'];

    public function adAccounts(): HasMany
    {
        return $this->hasMany(AdAccount::class);
    }
}
