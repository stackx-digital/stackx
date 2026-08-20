<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardItem extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'board_id',
        'source',
        'source_id',
        'title',
        'body',
        'media_url',
        'note',
        'created_by',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
