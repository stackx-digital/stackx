<?php

namespace App\Services\Embedding;

use App\Models\Ad;
use App\Models\AdEmbedding;
use App\Models\Competitor;
use App\Support\CurrentOrganization;

/**
 * Indexes our ads (names) and competitor ads (bodies) into ad_embeddings for
 * semantic search (§3, P3). Batched; unchanged content is skipped to avoid
 * re-embedding cost; failures are non-fatal — search keeps working on whatever
 * is already indexed.
 */
class EmbeddingService
{
    public function __construct(
        private readonly EmbeddingManager $embeddings,
        private readonly CurrentOrganization $org,
    ) {}

    public function embedCorpus(): EmbeddingResult
    {
        $result = new EmbeddingResult;
        $orgId = $this->org->id();
        $model = (string) config('embedding.model');
        $dims = (int) config('embedding.dims');

        $items = $this->collectItems($orgId);
        $existing = $this->existingByKey($model);

        // Only embed items whose content is new or changed.
        $pending = array_values(array_filter($items, function ($item) use ($existing, &$result) {
            $key = $item['source'].':'.$item['source_id'];
            if (($existing[$key] ?? null) === $item['content']) {
                $result->skipped++;

                return false;
            }

            return true;
        }));

        foreach (array_chunk($pending, (int) config('embedding.batch_size', 64)) as $batch) {
            $result->batches++;
            try {
                $vectors = $this->embeddings->driver()->embed(array_column($batch, 'content'));
            } catch (EmbeddingException $e) {
                $result->failed($e->getMessage());

                continue;
            }

            foreach ($batch as $i => $item) {
                if (! isset($vectors[$i])) {
                    continue;
                }

                AdEmbedding::updateOrCreate(
                    ['source' => $item['source'], 'source_id' => $item['source_id'], 'kind' => 'content'],
                    [
                        'organization_id' => $item['organization_id'],
                        'content' => $item['content'],
                        'model' => $model,
                        'dims' => $dims,
                        'embedding' => AdEmbedding::toLiteral($vectors[$i]),
                    ],
                );
                $result->embedded++;
            }
        }

        return $result;
    }

    /** @return array<int, array{source:string, source_id:int, content:string, organization_id:int}> */
    private function collectItems(?int $orgId): array
    {
        $items = [];

        foreach (Ad::all() as $ad) {
            if (filled($ad->name)) {
                $items[] = [
                    'source' => 'ad',
                    'source_id' => $ad->id,
                    'content' => $ad->name,
                    'organization_id' => $ad->organization_id,
                ];
            }
        }

        foreach (Competitor::with('ads')->get() as $competitor) {
            foreach ($competitor->ads as $ca) {
                if (filled($ca->body)) {
                    $items[] = [
                        'source' => 'competitor_ad',
                        'source_id' => $ca->id,
                        'content' => $ca->body,
                        'organization_id' => $competitor->organization_id,
                    ];
                }
            }
        }

        return $items;
    }

    /** @return array<string, string> key "source:id" => content, for the given model */
    private function existingByKey(string $model): array
    {
        return AdEmbedding::where('model', $model)
            ->get(['source', 'source_id', 'content'])
            ->mapWithKeys(fn (AdEmbedding $e) => [$e->source.':'.$e->source_id => $e->content])
            ->all();
    }
}
