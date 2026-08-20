<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Services\Ai\Exceptions\AiException;
use App\Services\Tagging\VisionTagger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Vision-tag an ad from an uploaded creative image (P4 Phase 2). The image is
 * stored as the ad's thumbnail and classified via the AI layer's vision. All
 * output is AI-inferred and labelled as such.
 */
class VisionTagController extends Controller
{
    public function store(Request $request, Ad $ad, VisionTagger $tagger): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5 MB
        ]);

        $file = $request->file('image');

        try {
            $tagger->tag($ad, base64_encode($file->get()), $file->getMimeType());
        } catch (AiException $e) {
            return redirect()->route('analytics')->with('status',
                'Vision tagging did not run — '.$e->getMessage().' (check AI_PROVIDER and the API key).');
        }

        // Persist the creative as the ad thumbnail for future reference.
        $path = $file->store('thumbnails', 'public');
        $ad->update(['thumbnail_url' => Storage::url($path)]);

        return redirect()->route('analytics')->with('status', "Vision-tagged “{$ad->name}” from its creative.");
    }
}
