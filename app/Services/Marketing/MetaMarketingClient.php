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
        return filled($this->token()) && $this->accountIds() !== [];
    }

    /** The access token, trimmed of stray whitespace/newlines from pasting. */
    private function token(): string
    {
        return trim((string) config('meta.token'));
    }

    /**
     * HMAC-SHA256 of the access token keyed by the app secret — Meta's
     * appsecret_proof. Null when no app secret is configured.
     */
    private function appSecretProof(): ?string
    {
        $secret = trim((string) config('meta.app_secret'));

        if ($secret === '' || $this->token() === '') {
            return null;
        }

        return hash_hmac('sha256', $this->token(), $secret);
    }

    /**
     * A non-secret fingerprint of the token in use (first 4 + last 4 + length),
     * so a failing-but-valid-looking token can be traced to the exact value the
     * app sent — without exposing the token itself.
     */
    private function fingerprint(): string
    {
        $t = $this->token();

        if ($t === '') {
            return 'EMPTY';
        }

        return substr($t, 0, 4).'…'.substr($t, -4).' ('.strlen($t).' chars)';
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
        if (filled($this->token()) === false) {
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
            'access_token' => $this->token(),
        ];

        // Prove the call comes from the app (required for server-side calls when
        // the app enforces it; fixes "(#200) Provide valid app ID" in dev mode).
        if (($proof = $this->appSecretProof()) !== null) {
            $params['appsecret_proof'] = $proof;
        }

        $insights = [];
        $maxPages = (int) config('meta.max_pages', 25);

        for ($page = 0; $page < $maxPages; $page++) {
            $response = Http::timeout((int) config('meta.timeout', 60))->get($url, $params);

            if ($response->failed()) {
                $error = (array) $response->json('error', []);
                $message = $error['message'] ?? 'request failed';
                $subcode = isset($error['error_subcode']) ? " subcode {$error['error_subcode']}" : '';

                // Diagnostic tail: which token/version the app actually used, so
                // a valid-looking token that still fails can be traced.
                throw new MetaException(sprintf(
                    'Meta API error (%s): %s%s · using token %s on %s',
                    $accountId, $message, $subcode, $this->fingerprint(), $version,
                ));
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

            // The `next` cursor is a full URL with the token baked in, but not
            // the appsecret_proof — append it so paginated pages stay authorized.
            if (($proof = $this->appSecretProof()) !== null && ! str_contains($next, 'appsecret_proof=')) {
                $next .= '&appsecret_proof='.$proof;
            }
            $url = $next;
            $params = [];
        }

        return $insights;
    }

    /**
     * Creative thumbnails for every ad in an account, keyed by ad id. Prefers
     * the full creative image over the small 64x64 thumbnail when Meta returns
     * one (video ads only get a thumbnail — a frame capture). Best-effort: a
     * failure here shouldn't break the insights sync, so callers may ignore it.
     *
     * @return array<string, string> ad_id => image url
     *
     * @throws MetaException
     */
    public function creativeThumbnails(string $accountId): array
    {
        $base = rtrim((string) config('meta.base_url'), '/');
        $version = config('meta.version');

        $url = "{$base}/{$version}/{$accountId}/ads";
        $params = [
            'fields' => 'id,creative{thumbnail_url,image_url}',
            'limit' => (int) config('meta.page_limit', 200),
            'access_token' => $this->token(),
        ];

        if (($proof = $this->appSecretProof()) !== null) {
            $params['appsecret_proof'] = $proof;
        }

        $thumbnails = [];
        $maxPages = (int) config('meta.max_pages', 25);

        for ($page = 0; $page < $maxPages; $page++) {
            $response = Http::timeout((int) config('meta.timeout', 60))->get($url, $params);

            if ($response->failed()) {
                $message = $response->json('error.message') ?? 'request failed';
                throw new MetaException("Meta API error ({$accountId}): {$message}");
            }

            foreach ((array) $response->json('data', []) as $node) {
                $id = $node['id'] ?? null;
                $creative = (array) ($node['creative'] ?? []);
                $image = $creative['image_url'] ?? $creative['thumbnail_url'] ?? null;

                if ($id !== null && $image !== null) {
                    $thumbnails[(string) $id] = (string) $image;
                }
            }

            $next = $response->json('paging.next');
            if (! is_string($next) || $next === '') {
                break;
            }

            if (($proof = $this->appSecretProof()) !== null && ! str_contains($next, 'appsecret_proof=')) {
                $next .= '&appsecret_proof='.$proof;
            }
            $url = $next;
            $params = [];
        }

        return $thumbnails;
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
