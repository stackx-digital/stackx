<?php

namespace App\Services\Ingest;

use Illuminate\Support\Str;

/**
 * Maps the wildly varying Meta Ads Manager export column labels (they change
 * by locale, currency, and attribution setting — "Amount spent (MYR)", "CTR
 * (link click-through rate)", "ThruPlays", …) onto our canonical fields (§5).
 *
 * Matching is done on a normalized header (lowercased, whitespace-collapsed)
 * using an ordered prefix ruleset — the first matching rule wins, so more
 * specific labels are listed before general fallbacks.
 */
class MetaHeaderMap
{
    /**
     * Ordered [needle, canonical] rules. A header matches when its normalized
     * form starts with the needle. Order matters (specific before general).
     *
     * @var array<int, array{0:string, 1:string}>
     */
    private const RULES = [
        // Identity
        ['ad name', 'ad_name'],
        ['ad id', 'meta_ad_id'],
        ['ad delivery', 'ad_status'],
        ['delivery status', 'ad_status'],

        // Date
        ['day', 'date'],
        ['date', 'date'],
        ['reporting starts', 'date'],

        // Spend
        ['amount spent', 'spend'],
        ['spend', 'spend'],

        // Volume
        ['impressions', 'impressions'],
        ['reach', 'reach'],

        // Click-through — link before all before bare "ctr"
        ['ctr (link', 'ctr_link'],
        ['link ctr', 'ctr_link'],
        ['ctr (all', 'ctr_all'],
        ['ctr', 'ctr_all'],

        // Cost ratios
        ['cpc', 'cpc'],
        ['cpm', 'cpm'],

        // Video
        ['thruplay', 'thruplays'],
        ['3-second video', 'video_3s'],
        ['3 second video', 'video_3s'],
        ['video plays at 3', 'video_3s'],

        // Outcomes — cost per result before results; roas variants
        ['cost per result', 'cost_per_result'],
        ['results', 'results'],
        ['result', 'results'],
        ['purchase roas', 'roas'],
        ['website purchase roas', 'roas'],
        ['roas', 'roas'],
    ];

    /** Canonical fields that identify/what we actually store. */
    public const METRIC_FIELDS = [
        'spend', 'impressions', 'reach', 'ctr_all', 'ctr_link', 'cpc', 'cpm',
        'thruplays', 'video_3s', 'results', 'cost_per_result', 'roas',
    ];

    /** Fields whose absence is worth warning the user about. */
    public const IMPORTANT_FIELDS = ['ad_name', 'spend', 'impressions'];

    public static function normalize(string $header): string
    {
        return Str::of($header)->lower()->squish()->value();
    }

    /** Canonical field for a header, or null if unrecognized. */
    public function canonicalFor(string $header): ?string
    {
        $normal = self::normalize($header);

        if ($normal === '') {
            return null;
        }

        foreach (self::RULES as [$needle, $field]) {
            if (str_starts_with($normal, $needle)) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Build a mapping from a list of CSV headers.
     *
     * @param  array<int, string>  $headers
     * @return array{mapping: array<string,string>, unknown: array<int,string>}
     *         mapping = canonical => original header (first header wins per field)
     */
    public function build(array $headers): array
    {
        $mapping = [];
        $unknown = [];

        foreach ($headers as $header) {
            $canonical = $this->canonicalFor($header);

            if ($canonical === null) {
                if (trim($header) !== '') {
                    $unknown[] = $header;
                }

                continue;
            }

            // First header to claim a canonical field wins.
            $mapping[$canonical] ??= $header;
        }

        return ['mapping' => $mapping, 'unknown' => $unknown];
    }
}
