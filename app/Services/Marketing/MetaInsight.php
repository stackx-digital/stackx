<?php

namespace App\Services\Marketing;

use Illuminate\Support\Arr;

/**
 * One ad-level, single-day insight row from the Marketing API, normalized onto
 * the same canonical fields the CSV importer produces (so scoring is identical).
 * Only fields Meta actually returned are set; the rest stay absent.
 */
class MetaInsight
{
    /** @param array<string, mixed> $node one element of the insights `data` array */
    public function __construct(private array $node) {}

    /** @return array<string, mixed> canonical field => value (importer row shape) */
    public function toRow(): array
    {
        $row = [
            'meta_ad_id' => (string) ($this->node['ad_id'] ?? ''),
            'ad_name' => (string) ($this->node['ad_name'] ?? ''),
            'date' => (string) ($this->node['date_start'] ?? ''),
        ];

        $this->put($row, 'spend', $this->num('spend'));
        $this->put($row, 'impressions', $this->num('impressions'));
        $this->put($row, 'reach', $this->num('reach'));
        $this->put($row, 'ctr_all', $this->num('ctr'));
        $this->put($row, 'ctr_link', $this->num('inline_link_click_ctr'));
        $this->put($row, 'cpc', $this->num('cpc'));
        $this->put($row, 'cpm', $this->num('cpm'));

        // ROAS: purchase_roas is [{action_type, value}]; take the first value.
        $this->put($row, 'roas', $this->firstActionValue('purchase_roas'));

        // Video: ThruPlays / 3-second plays are action-array fields.
        $this->put($row, 'thruplays', $this->actionValue('video_thruplay_watched_actions'));
        $this->put($row, 'video_3s', $this->actionValue('video_play_actions'));

        // Results / cost-per-result are objective-dependent. Prefer purchases;
        // fall back to the account's optimization result when present. Left null
        // when we can't attribute honestly — scoring has its own fallbacks.
        $results = $this->actionValue('actions', ['omni_purchase', 'purchase', 'offsite_conversion.fb_pixel_purchase']);
        $this->put($row, 'results', $results);
        $this->put($row, 'cost_per_result', $this->actionValue('cost_per_action_type', ['omni_purchase', 'purchase', 'offsite_conversion.fb_pixel_purchase']));

        return $row;
    }

    private function num(string $key): ?float
    {
        $v = $this->node[$key] ?? null;

        return is_numeric($v) ? (float) $v : null;
    }

    /** First numeric `value` from an action-array field (e.g. purchase_roas). */
    private function firstActionValue(string $key): ?float
    {
        $items = $this->node[$key] ?? null;
        if (! is_array($items)) {
            return null;
        }

        foreach ($items as $item) {
            if (isset($item['value']) && is_numeric($item['value'])) {
                return (float) $item['value'];
            }
        }

        return null;
    }

    /**
     * Numeric `value` from an action-array field, optionally filtered to the
     * first matching action_type in $prefer; otherwise the sum of all values.
     *
     * @param  array<int, string>  $prefer
     */
    private function actionValue(string $key, array $prefer = []): ?float
    {
        $items = $this->node[$key] ?? null;
        if (! is_array($items)) {
            return null;
        }

        if ($prefer !== []) {
            foreach ($prefer as $type) {
                foreach ($items as $item) {
                    if (($item['action_type'] ?? null) === $type && is_numeric($item['value'] ?? null)) {
                        return (float) $item['value'];
                    }
                }
            }

            return null;
        }

        $sum = 0.0;
        $seen = false;
        foreach ($items as $item) {
            if (is_numeric($item['value'] ?? null)) {
                $sum += (float) $item['value'];
                $seen = true;
            }
        }

        return $seen ? $sum : null;
    }

    /** @param array<string, mixed> $row */
    private function put(array &$row, string $field, ?float $value): void
    {
        if ($value !== null) {
            $row[$field] = $value;
        }
    }

    public function hasIdentity(): bool
    {
        return Arr::get($this->node, 'ad_id') || Arr::get($this->node, 'ad_name');
    }
}
