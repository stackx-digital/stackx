<?php

namespace App\Http\Controllers;

use App\Models\AdEmbedding;
use App\Services\Embedding\EmbeddingException;
use App\Services\Embedding\EmbeddingService;
use App\Services\Embedding\SemanticSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P3 Ad Discovery — pgvector semantic search across our ads and competitor ads.
 * Similarity is a computed cosine score, shown honestly. Embeddings are
 * generated on demand (and by the discovery:embed schedule).
 */
class DiscoveryController extends Controller
{
    public function index(Request $request, SemanticSearch $search): Response
    {
        $query = trim((string) $request->get('q', ''));
        $embeddedCount = AdEmbedding::count();
        $results = [];
        $error = null;

        if ($query !== '' && $embeddedCount > 0) {
            try {
                $results = $search->search($query, 20);
            } catch (EmbeddingException $e) {
                $error = 'Search needs an embedding provider — '.$e->getMessage();
            }
        }

        return Inertia::render('Discovery/Index', [
            'query' => $query,
            'results' => $results,
            'embeddedCount' => $embeddedCount,
            'embeddingModel' => config('embedding.model'),
            'searchError' => $error,
        ]);
    }

    public function embed(EmbeddingService $service): RedirectResponse
    {
        $result = $service->embedCorpus();

        if ($result->embedded === 0 && $result->failedBatches > 0) {
            return redirect()->route('discovery')->with('status',
                'Embedding did not run — '.($result->errors[0] ?? 'provider not configured').' (set OPENAI_API_KEY).');
        }

        return redirect()->route('discovery')->with('status', sprintf(
            'Indexed %d item(s); %d unchanged.',
            $result->embedded,
            $result->skipped,
        ));
    }
}
