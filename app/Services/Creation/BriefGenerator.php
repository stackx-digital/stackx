<?php

namespace App\Services\Creation;

use App\Models\Ad;
use App\Models\CompetitorAd;
use App\Models\CreativeBrief;
use App\Services\Ai\Exceptions\AiException;
use App\Support\Facades\Ai;
use Illuminate\Support\Str;

/**
 * Turns a winning ad, a competitor ad, or a free-form product into a structured
 * creative brief for designers/copywriters (feature 2/4), via the AI layer. All
 * output is AI-generated and labelled as such; failures surface as AiException.
 */
class BriefGenerator
{
    private const SYSTEM = <<<'PROMPT'
        You are a creative strategist at a Malaysian performance-marketing agency.
        Produce a concise, actionable creative brief a designer + copywriter can
        execute. Malaysian market context (BM + English where natural).
        Return JSON only, no prose:
        {"objective":"..","target_audience":"..","big_idea":"..","angle":"..",
         "hooks":["..","..",".."],"visual_direction":"..","copy_points":["..",".."],"cta":".."}
        PROMPT;

    /**
     * @param  array{source?:?string, source_id?:?int, product:string, created_by?:?int}  $input
     *
     * @throws AiException
     */
    public function generate(array $input): CreativeBrief
    {
        [$sourceType, $sourceId, $reference] = $this->resolveSource($input);

        $user = "Product / offer: {$input['product']}";
        if ($reference) {
            $user .= PHP_EOL."Inspired by this proven creative: {$reference}";
        }

        $response = Ai::structuredJson(self::SYSTEM, $user);

        return CreativeBrief::create([
            'source' => $sourceType,
            'source_id' => $sourceId,
            'product' => $input['product'],
            'output' => $this->validate($response),
            'generated_by' => $this->generatedBy(),
            'created_by' => $input['created_by'] ?? null,
        ]);
    }

    /** @return array{0:?string, 1:?int, 2:?string} */
    private function resolveSource(array $input): array
    {
        $type = $input['source'] ?? null;
        $id = $input['source_id'] ?? null;

        if ($type === 'ad' && $id && ($ad = Ad::find($id))) {
            return ['ad', (int) $id, "Winning ad: \"{$ad->name}\""];
        }
        if ($type === 'competitor_ad' && $id && ($ca = CompetitorAd::with('competitor')->find($id))) {
            return ['competitor_ad', (int) $id, "Competitor ad ({$ca->competitor?->name}): \"".Str::limit($ca->body, 300).'"'];
        }

        return [null, null, null];
    }

    /**
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function validate(array $r): array
    {
        $str = fn ($v) => is_string($v) && trim($v) !== '' ? trim($v) : null;
        $list = fn ($v) => is_array($v) ? array_values(array_filter(array_map($str, $v))) : [];

        return [
            'objective' => $str($r['objective'] ?? null),
            'target_audience' => $str($r['target_audience'] ?? null),
            'big_idea' => $str($r['big_idea'] ?? null),
            'angle' => $str($r['angle'] ?? null),
            'hooks' => $list($r['hooks'] ?? []),
            'visual_direction' => $str($r['visual_direction'] ?? null),
            'copy_points' => $list($r['copy_points'] ?? []),
            'cta' => $str($r['cta'] ?? null),
        ];
    }

    private function generatedBy(): string
    {
        try {
            $d = Ai::driver();

            return $d->name().':'.$d->model();
        } catch (\Throwable) {
            return 'ai';
        }
    }
}
