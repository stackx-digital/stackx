<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/**
 * A stored vector embedding. The `embedding` column is written/read as a
 * literal string ("[0.1,0.2,...]") that is valid both as a pgvector value and
 * as a JSON array (sqlite fallback), so it is not cast here — the search layer
 * decodes it when running the sqlite cosine path.
 */
class AdEmbedding extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'source',
        'source_id',
        'kind',
        'content',
        'model',
        'dims',
        'embedding',
    ];

    protected function casts(): array
    {
        return ['dims' => 'integer'];
    }

    /** @param array<int, float> $vector */
    public static function toLiteral(array $vector): string
    {
        return '['.implode(',', array_map(static fn ($v) => (float) $v, $vector)).']';
    }

    /** @return array<int, float> */
    public function vector(): array
    {
        return array_map('floatval', json_decode((string) $this->embedding, true) ?: []);
    }
}
