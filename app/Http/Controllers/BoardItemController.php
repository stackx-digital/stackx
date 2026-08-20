<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Board;
use App\Models\BoardItem;
use App\Models\CompetitorAd;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Add/remove items on a Creative Library board. Items reference our ad or a
 * competitor ad, or store a pasted external swipe.
 */
class BoardItemController extends Controller
{
    public function store(Request $request, Board $board): RedirectResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'in:ad,competitor_ad,external'],
            'source_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'media_url' => ['nullable', 'string', 'max:2048'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $attrs = $this->resolve($validated);
        $attrs['note'] = $validated['note'] ?? null;
        $attrs['created_by'] = $request->user()->id;

        $board->items()->create($attrs);

        return redirect()->route('boards.show', $board)->with('status', 'Saved to board.');
    }

    /** @return array<string, mixed> */
    private function resolve(array $v): array
    {
        if ($v['source'] === 'ad' && $v['source_id']) {
            $ad = Ad::find($v['source_id']);

            return [
                'source' => 'ad',
                'source_id' => $ad?->id,
                'title' => $ad?->name ?? 'Ad',
                'body' => null,
                'media_url' => $ad?->thumbnail_url,
            ];
        }

        if ($v['source'] === 'competitor_ad' && $v['source_id']) {
            $ca = CompetitorAd::with('competitor')->find($v['source_id']);

            return [
                'source' => 'competitor_ad',
                'source_id' => $ca?->id,
                'title' => $ca?->competitor?->name ?? 'Competitor ad',
                'body' => $ca?->body,
                'media_url' => $ca?->media_url,
            ];
        }

        // External paste.
        return [
            'source' => 'external',
            'source_id' => null,
            'title' => $v['title'] ?: Str::limit($v['body'] ?? 'Saved ad', 40),
            'body' => $v['body'] ?? null,
            'media_url' => $v['media_url'] ?? null,
        ];
    }

    public function destroy(BoardItem $item): RedirectResponse
    {
        $board = $item->board;
        $item->delete();

        return redirect()->route('boards.show', $board)->with('status', 'Removed from board.');
    }
}
