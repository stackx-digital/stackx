<?php

namespace App\Services\Reporting;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Posts a report summary to a Slack incoming webhook (§5, P5). No-ops cleanly
 * when no webhook is configured, so the scheduled job never fails the run.
 */
class SlackNotifier
{
    public function configured(): bool
    {
        return filled(config('services.slack.webhook'));
    }

    /**
     * @param  array<string, mixed>  $payload  a ReportBuilder payload
     * @return bool  true if a message was sent
     */
    public function sendReport(array $payload, string $title, ?string $url = null): bool
    {
        if (! $this->configured()) {
            return false;
        }

        try {
            $response = Http::timeout(15)->post(config('services.slack.webhook'), [
                'text' => $this->summaryText($payload, $title, $url),
            ]);

            return $response->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /** Post a plain text message to the webhook. Returns true if sent. */
    public function sendText(string $text): bool
    {
        if (! $this->configured()) {
            return false;
        }

        try {
            return Http::timeout(15)->post(config('services.slack.webhook'), ['text' => $text])->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /** @param array<string, mixed> $payload */
    private function summaryText(array $payload, string $title, ?string $url): string
    {
        $s = $payload['summary'] ?? [];
        $spend = isset($s['totalSpend']) ? 'RM'.number_format((float) $s['totalSpend'], 0) : '—';
        $roas = $s['blendedRoas'] ?? '—';
        $cpa = isset($s['blendedCpa']) ? 'RM'.number_format((float) $s['blendedCpa'], 2) : '—';

        $lines = [
            "*STACKx — {$title}*",
            "Spend: {$spend}  ·  Blended ROAS: {$roas}  ·  CPA: {$cpa}  ·  Ads: ".($s['adCount'] ?? 0),
        ];

        $winners = collect($payload['winners'] ?? [])->take(3)
            ->map(fn ($w) => '• '.$w['name'].' ('.($w['roas'] ?? '—').'×)')->all();
        if ($winners) {
            $lines[] = "*Scale:*\n".implode("\n", $winners);
        }

        $losers = collect($payload['losers'] ?? [])->take(3)
            ->map(fn ($l) => '• '.$l['name'].' ('.($l['roas'] ?? '—').'×)')->all();
        if ($losers) {
            $lines[] = "*Cut:*\n".implode("\n", $losers);
        }

        if ($url) {
            $lines[] = "<{$url}|View full report>";
        }

        return implode("\n\n", $lines);
    }
}
