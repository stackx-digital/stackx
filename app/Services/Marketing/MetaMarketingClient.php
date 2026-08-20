<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Thin client over the Meta Marketing API insights endpoint. Reads ad-level,
 * daily creative performance for one or more ad accounts. Credentials come from
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
        return filled(config('meta.token')) && $this->accountIds() !== [];
    }

    /**
     * Every configured ad account, normalized to "act_…". Accepts a single id
     * or several separated by comma / newline / whitespace, so an agency can
     * connect all of its client accounts.
     *
     * @return array<int, string>
     */
    public function accountIds(): array
    {
        $raw = (string) config('meta.ad_account_id');

        return collect(preg_split('/[\s,]+/', $raw, flags: PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn ($id) => Str::startsWith($id, 'act_') ? $id : 'act_'.$id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Fetch ad-level daily insights for one ad account over the last
     * $lookbackDays.
     *
     * @return array<int, MetaInsight>
     *
     * @throws MetaException
     */
    public function insights(string $accountId, ?int $lookbackDays = null): array
    {
        if (filled(config('meta.token')) === false) {
            throw new MetaException('Meta is not connected — add a System User token in Settings.');
        }

        $days = $lookbackDays ?? (int) config('meta.lookback_days', 30);
        $base = rtrim((string) config('meta.base_url'), '/');
        $version = config('meta.version');

        $url = "{$base}/{$version}/{$accountId}/insights";
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
                throw new MetaException("Meta API error ({$accountId}): {$message}");
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
            default => 'last_90d',
        };
    }
}
