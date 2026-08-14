<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdVariation extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'source',
        'source_id',
        'product',
        'prompt',
        'output',
        'generated_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['output' => 'array'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
