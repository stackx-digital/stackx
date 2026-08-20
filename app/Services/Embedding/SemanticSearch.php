<?php

namespace App\Services\Embedding;

use App\Models\Ad;
use App\Models\AdEmbedding;
use App\Models\CompetitorAd;
use App\Support\CurrentOrganization;
use Illuminate\Support\Facades\DB;

/**
 * pgvector semantic search (§2 P3). Embeds the query, then finds nearest
 * neighbours: the native `<=>` cosine operator on Postgres, or an in-PHP cosine
 * fallback on sqlite (local/testing). Similarity is a computed cosine score —
 * shown honestly, never inflated.
 */
class SemanticSearch
{
    public function __construct(
        private readonly EmbeddingManager $embeddings,
        private readonly CurrentOrganization $org,
    ) {}

    /**
     * @return array<int, array{source:string, sourceId:int, title:string, snippet:?string, similarity:float, competitor:?string}>
     *
     * @throws EmbeddingException
     */
    public function search(string $query, int $k = 20): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $vector = $this->embeddings->driver()->embed([$query])[0] ?? null;
        if (! $vector) {
            throw new EmbeddingException('Query embedding failed.');
        }

        $ranked = DB::getDriverName() === 'pgsql'
            ? $this->searchPgvector($vector, $k)
            : $this->searchCosine($vector, $k);

        return $this->hydrate($ranked);
    }

    /**
     * @param  array<int, float>  $vector
     * @return array<int, array{source:string, source_id:int, similarity:float}>
     */
    private function searchPgvector(array $vector, int $k): array
    {
        $literal = AdEmbedding::toLiteral($vector);
        $k = max(1, min(100, $k));

        $rows = DB::select(
            'SELECT source, source_id, 1 - (embedding <=> ?::vector) AS similarity
             FROM ad_embeddings
             WHERE organization_id = ?
             ORDER BY embedding <=> ?::vector
             LIMIT '.$k,
            [$literal, $this->org->id(), $literal],
        );

        return array_map(fn ($r) => [
            'source' => $r->source,
            'source_id' => (int) $r->source_id,
            'similarity' => round((float) $r->similarity, 4),
        ], $rows);
    }

    /**
     * @param  array<int, float>  $vector
     * @return array<int, array{source:string, source_id:int, similarity:float}>
     */
    private function searchCosine(array $vector, int $k): array
    {
        $scored = AdEmbedding::all()->map(fn (AdEmbedding $e) => [
            'source' => $e->source,
            'source_id' => (int) $e->source_id,
            'similarity' => round($this->cosine($vector, $e->vector()), 4),
        ]);

        return $scored
            ->sortByDesc('similarity')
            ->take($k)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    private function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        $n = min(count($a), count($b));

        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] ** 2;
            $nb += $b[$i] ** 2;
        }

        $denom = sqrt($na) * sqrt($nb);

        return $denom > 0 ? $dot / $denom : 0.0;
    }

    /**
     * @param  array<int, array{source:string, source_id:int, similarity:float}>  $ranked
     * @return array<int, array{source:string, sourceId:int, title:string, snippet:?string, similarity:float, competitor:?string}>
     */
    private function hydrate(array $ranked): array
    {
        $adIds = collect($ranked)->where('source', 'ad')->pluck('source_id');
        $caIds = collect($ranked)->where('source', 'competitor_ad')->pluck('source_id');

        $ads = Ad::whereIn('id', $adIds)->get()->keyBy('id');
        $cas = CompetitorAd::with('competitor')->whereIn('id', $caIds)->get()->keyBy('id');

        $out = [];
        foreach ($ranked as $r) {
            if ($r['source'] === 'ad') {
                $ad = $ads->get($r['source_id']);
                if (! $ad) {
                    continue;
                }
                $out[] = [
                    'source' => 'ad',
                    'sourceId' => $ad->id,
                    'title' => $ad->name,
                    'snippet' => null,
                    'similarity' => $r['similarity'],
                    'competitor' => null,
                ];
            } else {
                $ca = $cas->get($r['source_id']);
                if (! $ca) {
                    continue;
                }
                $out[] = [
                    'source' => 'competitor_ad',
                    'sourceId' => $ca->id,
                    'title' => $ca->competitor?->name ?? 'Competitor',
                    'snippet' => $ca->body,
                    'similarity' => $r['similarity'],
                    'competitor' => $ca->competitor?->name,
                ];
            }
        }

        return $out;
    }
}
