<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'name', 'description', 'created_by'];

    public function items(): HasMany
    {
        return $this->hasMany(BoardItem::class);
    }
}
