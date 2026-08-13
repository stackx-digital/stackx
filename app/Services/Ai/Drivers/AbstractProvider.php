<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Contracts\AiProvider;
use App\Services\Ai\Exceptions\AiException;
use Illuminate\Support\Str;

/**
 * Shared plumbing for AI drivers: config access and tolerant JSON decoding
 * (providers sometimes wrap JSON in ```json fences despite instructions).
 */
abstract class AbstractProvider implements AiProvider
{
    /** @param  array<string, mixed>  $config */
    public function __construct(
        protected array $config,
        protected int $timeout = 60,
        protected int $retries = 2,
    ) {}

    public function model(): string
    {
        return (string) ($this->config['model'] ?? '');
    }

    /**
     * Decode a model's text response into a JSON object, stripping any
     * Markdown code fences first.
     *
     * @return array<string, mixed>
     */
    protected function decodeJson(string $text): array
    {
        $clean = trim($text);

        // Strip a leading ```json / ``` fence and trailing ``` if present.
        if (Str::startsWith($clean, '```')) {
            $clean = preg_replace('/^```[a-zA-Z]*\s*/', '', $clean);
            $clean = preg_replace('/\s*```$/', '', (string) $clean);
        }

        $decoded = json_decode((string) $clean, true);

        if (! is_array($decoded)) {
            throw new AiException(
                "AI response was not valid JSON: {$this->name()} returned: ".Str::limit($text, 300),
            );
        }

        return $decoded;
    }

    protected function requireKey(): string
    {
        $key = $this->config['key'] ?? null;

        if (blank($key)) {
            throw new AiException(
                "Missing API key for AI provider [{$this->name()}]. Set it in .env.",
            );
        }

        return (string) $key;
    }
}
