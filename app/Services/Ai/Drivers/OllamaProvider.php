<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Exceptions\AiException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Ollama Cloud driver — its hosted API is OpenAI-compatible
 * (https://ollama.com/v1/chat/completions), so the request/response shape
 * mirrors OpenAiProvider. Use a cloud model tag (e.g. "gpt-oss:120b-cloud",
 * "qwen3-coder:480b-cloud") — non-cloud/local tags won't resolve against the
 * hosted API.
 */
class OllamaProvider extends AbstractProvider
{
    public function name(): string
    {
        return 'ollama';
    }

    public function structuredJson(string $system, string $user, array $options = []): array
    {
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? 'https://ollama.com'), '/');

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
            throw new AiException("Ollama request failed: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new AiException(
                "Ollama API error ({$response->status()}): ".$response->body(),
            );
        }

        $text = $response->json('choices.0.message.content');

        if (! is_string($text)) {
            throw new AiException('Ollama response had no message content.');
        }

        return $this->decodeJson($text);
    }

    public function visionJson(string $system, string $user, string $imageBase64, string $mediaType, array $options = []): array
    {
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? 'https://ollama.com'), '/');

        try {
            $response = Http::withToken($this->requireKey())
                ->timeout($this->timeout)
                ->retry($this->retries, 500)
                ->post("{$baseUrl}/v1/chat/completions", [
                    'model' => $options['model'] ?? $this->model(),
                    'max_tokens' => $options['max_tokens'] ?? ($this->config['max_tokens'] ?? 4096),
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system.' Respond with JSON only.'],
                        ['role' => 'user', 'content' => [
                            ['type' => 'text', 'text' => $user],
                            ['type' => 'image_url', 'image_url' => ['url' => "data:{$mediaType};base64,{$imageBase64}"]],
                        ]],
                    ],
                ]);
        } catch (Throwable $e) {
            throw new AiException("Ollama vision request failed: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new AiException("Ollama vision API error ({$response->status()}): ".$response->body());
        }

        $text = $response->json('choices.0.message.content');

        if (! is_string($text)) {
            throw new AiException('Ollama vision response had no message content.');
        }

        return $this->decodeJson($text);
    }
}
