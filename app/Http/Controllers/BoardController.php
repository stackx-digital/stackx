<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Board;
use App\Models\BoardItem;
use App\Models\Competitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Creative Library — swipe boards. Org-scoped collections of saved winning /
 * inspiring ads (ours, competitors', or pasted). No AI; pure organisation.
 */
class BoardController extends Controller
{
    public function index(): Response
    {
        $boards = Board::withCount('items')->latest()->get()->map(fn (Board $b) => [
            'id' => $b->id,
            'name' => $b->name,
            'description' => $b->description,
            'itemCount' => $b->items_count,
        ]);

        return Inertia::render('Library/Index', ['boards' => $boards]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $board = Board::create([...$validated, 'created_by' => $request->user()->id]);

        return redirect()->route('boards.show', $board)->with('status', "Board “{$board->name}” created.");
    }

    public function show(Board $board): Response
    {
        $board->load(['items' => fn ($q) => $q->latest()]);

        return Inertia::render('Library/Show', [
            'board' => [
                'id' => $board->id,
                'name' => $board->name,
                'description' => $board->description,
            ],
            'items' => $board->items->map(fn (BoardItem $i) => [
                'id' => $i->id,
                'source' => $i->source,
                'title' => $i->title,
                'body' => $i->body,
                'mediaUrl' => $i->media_url,
                'note' => $i->note,
            ]),
            'sources' => [
                'ads' => Ad::orderBy('name')->get()->map(fn (Ad $a) => [
                    'id' => $a->id, 'label' => $a->name,
                ]),
                'competitorAds' => Competitor::with('ads')->get()
                    ->flatMap(fn (Competitor $c) => $c->ads->map(fn ($ad) => [
                        'id' => $ad->id,
                        'label' => $c->name.' — '.Str::limit($ad->body, 50),
                    ]))->values(),
            ],
        ]);
    }

    public function destroy(Board $board): RedirectResponse
    {
        $board->delete();

        return redirect()->route('library')->with('status', 'Board deleted.');
    }
}
