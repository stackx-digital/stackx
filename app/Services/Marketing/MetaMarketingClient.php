<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Thin client over the Meta Marketing API insights endpoint. Reads ad-level,
 * daily creative performance for one ad account. Credentials come from
 * config('meta') — overlaid per tenant by ApplyTenantSettings.
 */
class MetaMarketingClient
{
    private const FIELDS = [
        'ad_id', 'ad_name', 'spend', 'impressions', 'reach', 'ctr',
        'inline_link_click_ctr', 'cpc', 'cpm', 'purchase_roas', 'actions',
        'cost_per_action_type', 'video_thruplay_watched_actions',
        'video_play_actions',
    ];

    public function enabled(): bool
    {
        return filled(config('meta.token')) && filled(config('meta.ad_account_id'));
    }

    /** Normalize "123" or "act_123" to the "act_123" the API expects. */
    public function accountId(): string
    {
        $id = trim((string) config('meta.ad_account_id'));

        return Str::startsWith($id, 'act_') ? $id : 'act_'.$id;
    }

    /**
     * Fetch ad-level daily insights for the last $lookbackDays.
     *
     * @return array<int, MetaInsight>
     *
     * @throws MetaException
     */
    public function insights(?int $lookbackDays = null): array
    {
        if (! $this->enabled()) {
            throw new MetaException('Meta is not connected — add a System User token and ad account id in Settings.');
        }

        $days = $lookbackDays ?? (int) config('meta.lookback_days', 30);
        $base = rtrim((string) config('meta.base_url'), '/');
        $version = config('meta.version');

        $url = "{$base}/{$version}/{$this->accountId()}/insights";
        $params = [
            'level' => 'ad',
            'time_increment' => 1,
            'date_preset' => $this->datePreset($days),
            'fields' => implode(',', self::FIELDS),
            'limit' => (int) config('meta.page_limit', 200),
            'access_token' => config('meta.token'),
        ];

        $insights = [];
        $maxPages = (int) config('meta.max_pages', 25);

        for ($page = 0; $page < $maxPages; $page++) {
            $response = Http::timeout((int) config('meta.timeout', 60))->get($url, $params);

            if ($response->failed()) {
                $message = $response->json('error.message') ?? 'request failed';
                throw new MetaException("Meta API error: {$message}");
            }

            foreach ((array) $response->json('data', []) as $node) {
                $insight = new MetaInsight($node);
                if ($insight->hasIdentity()) {
                    $insights[] = $insight;
                }
            }

            $next = $response->json('paging.next');
            if (! is_string($next) || $next === '') {
                break;
            }

            // The `next` cursor is a full URL with the token + params baked in.
            $url = $next;
            $params = [];
        }

        return $insights;
    }

    /** Map a day count to Meta's nearest date_preset bucket. */
    private function datePreset(int $days): string
    {
        return match (true) {
            $days <= 7 => 'last_7d',
            $days <= 14 => 'last_14d',
            $days <= 30 => 'last_30d',
            $days <= 90 => 'last_90d',
            default => 'last_90d',
        };
    }
}
