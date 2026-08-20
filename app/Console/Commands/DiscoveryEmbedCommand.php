<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Embedding\EmbeddingService;
use App\Support\CurrentOrganization;
use Illuminate\Console\Command;

/**
 * Scheduled embedding index refresh (P3). Runs per organization so scoping is
 * correct. New/changed content is embedded; unchanged is skipped.
 */
class DiscoveryEmbedCommand extends Command
{
    protected $signature = 'discovery:embed';

    protected $description = 'Generate/refresh semantic-search embeddings for ads and competitor ads.';

    public function handle(EmbeddingService $service, CurrentOrganization $org): int
    {
        foreach (Organization::all() as $organization) {
            $org->set($organization->id);
            $result = $service->embedCorpus();
            $this->info(sprintf(
                '%s: %d embedded, %d unchanged, %d failed batch(es).',
                $organization->name, $result->embedded, $result->skipped, $result->failedBatches,
            ));
        }

        return self::SUCCESS;
    }
}
