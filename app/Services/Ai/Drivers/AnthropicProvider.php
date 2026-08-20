<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Exceptions\AiException;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Anthropic (Claude) driver — Messages API. */
class AnthropicProvider extends AbstractProvider
{
    public function name(): string
    {
        return 'anthropic';
    }

    public function structuredJson(string $system, string $user, array $options = []): array
    {
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? 'https://api.anthropic.com'), '/');

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->requireKey(),
                'anthropic-version' => (string) ($this->config['version'] ?? '2023-06-01'),
                'content-type' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->retry($this->retries, 500)
                ->post("{$baseUrl}/v1/messages", [
                    'model' => $options['model'] ?? $this->model(),
                    'max_tokens' => $options['max_tokens'] ?? ($this->config['max_tokens'] ?? 4096),
                    'temperature' => $options['temperature'] ?? 0,
                    // Nudge JSON-only output; callers still validate the shape.
                    'system' => $system."\n\nRespond with JSON only. No prose, no code fences.",
                    'messages' => [
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);
        } catch (Throwable $e) {
            throw new AiException("Anthropic request failed: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new AiException(
                "Anthropic API error ({$response->status()}): ".$response->body(),
            );
        }

        $text = $response->json('content.0.text');

        if (! is_string($text)) {
            throw new AiException('Anthropic response had no text content.');
        }

        return $this->decodeJson($text);
    }

    public function visionJson(string $system, string $user, string $imageBase64, string $mediaType, array $options = []): array
    {
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? 'https://api.anthropic.com'), '/');

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->requireKey(),
                'anthropic-version' => (string) ($this->config['version'] ?? '2023-06-01'),
                'content-type' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->retry($this->retries, 500)
                ->post("{$baseUrl}/v1/messages", [
                    'model' => $options['model'] ?? $this->model(),
                    'max_tokens' => $options['max_tokens'] ?? ($this->config['max_tokens'] ?? 4096),
                    'temperature' => 0,
                    'system' => $system."\n\nRespond with JSON only. No prose, no code fences.",
                    'messages' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mediaType, 'data' => $imageBase64]],
                            ['type' => 'text', 'text' => $user],
                        ],
                    ]],
                ]);
        } catch (Throwable $e) {
            throw new AiException("Anthropic vision request failed: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new AiException("Anthropic vision API error ({$response->status()}): ".$response->body());
        }

        $text = $response->json('content.0.text');

        if (! is_string($text)) {
            throw new AiException('Anthropic vision response had no text content.');
        }

        return $this->decodeJson($text);
    }
}
