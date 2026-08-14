<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\AdVariation;
use App\Models\Competitor;
use App\Services\Ai\Exceptions\AiException;
use App\Services\Creation\VariationGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P4 Ad Creation. Turns a winning ad (ours or a competitor's), or a free-form
 * brief, into AI-generated copy variations. All output is clearly AI-generated.
 */
class CreateController extends Controller
{
    public function index(): Response
    {
        $winners = Ad::with('score')->get()
            ->filter(fn (Ad $a) => $a->score !== null)
            ->map(fn (Ad $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'action' => $a->score->action,
            ])->values();

        $competitorAds = Competitor::with(['ads' => fn ($q) => $q->orderByDesc('days_running')])
            ->get()
            ->flatMap(fn (Competitor $c) => $c->ads->take(10)->map(fn ($ad) => [
                'id' => $ad->id,
                'label' => $c->name.' — '.\Illuminate\Support\Str::limit($ad->body, 60),
            ]))
            ->values();

        $history = AdVariation::with('creator')->latest()->limit(20)->get()
            ->map(fn (AdVariation $v) => [
                'id' => $v->id,
                'product' => $v->product,
                'source' => $v->source,
                'output' => $v->output,
                'generatedBy' => $v->generated_by,
                'createdBy' => $v->creator?->name,
                'createdAt' => $v->created_at?->diffForHumans(),
            ]);

        return Inertia::render('Create/Index', [
            'winners' => $winners,
            'competitorAds' => $competitorAds,
            'history' => $history,
        ]);
    }

    public function store(Request $request, VariationGenerator $generator): RedirectResponse
    {
        $validated = $request->validate([
            'product' => ['required', 'string', 'max:500'],
            'source' => ['nullable', 'in:ad,competitor_ad'],
            'source_id' => ['nullable', 'integer'],
            'tone' => ['nullable', 'string', 'max:100'],
            'count' => ['nullable', 'integer', 'min:1', 'max:8'],
            'language' => ['nullable', 'in:mix,bm,en'],
        ]);

        try {
            $variation = $generator->generate([
                ...$validated,
                'created_by' => $request->user()->id,
            ]);
        } catch (AiException $e) {
            return redirect()->route('create')->with('status',
                'AI generation did not run — '.$e->getMessage().' (check AI_PROVIDER and the API key).');
        }

        return redirect()->route('create')->with('status',
            'Generated '.count($variation->output).' variation(s).');
    }
}
