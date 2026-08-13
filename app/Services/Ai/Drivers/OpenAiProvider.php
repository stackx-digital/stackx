<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Exceptions\AiException;
use Illuminate\Support\Facades\Http;
use Throwable;

/** OpenAI driver — Chat Completions API with JSON response format. */
class OpenAiProvider extends AbstractProvider
{
    public function name(): string
    {
        return 'openai';
    }

    public function structuredJson(string $system, string $user, array $options = []): array
    {
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? 'https://api.openai.com'), '/');

        try {
            $response = Http::withToken($this->requireKey())
                ->timeout($this->timeout)
                ->retry($this->retries, 500)
                ->post("{$baseUrl}/v1/chat/completions", [
                    'model' => $options['model'] ?? $this->model(),
                    'max_tokens' => $options['max_tokens'] ?? ($this->config['max_tokens'] ?? 4096),
                    'temperature' => $options['temperature'] ?? 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system.' Respond with JSON only.'],
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);
        } catch (Throwable $e) {
            throw new AiException("OpenAI request failed: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new AiException(
                "OpenAI API error ({$response->status()}): ".$response->body(),
            );
        }

        $text = $response->json('choices.0.message.content');

        if (! is_string($text)) {
            throw new AiException('OpenAI response had no message content.');
        }

        return $this->decodeJson($text);
    }
}
