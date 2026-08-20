<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiProvider;
use App\Services\Ai\Drivers\AnthropicProvider;
use App\Services\Ai\Drivers\OpenAiProvider;
use Illuminate\Support\Manager;

/**
 * Resolves the configured AI provider using Laravel's Manager (driver)
 * pattern. Swap providers via AI_PROVIDER; call a specific one with
 * Ai::driver('openai'). Facade calls proxy to the default driver.
 *
 * @mixin AiProvider
 */
class AiManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('ai.default', 'anthropic');
    }

    protected function createAnthropicDriver(): AiProvider
    {
        return new AnthropicProvider(
            (array) $this->config->get('ai.providers.anthropic', []),
            (int) $this->config->get('ai.timeout', 60),
            (int) $this->config->get('ai.retries', 2),
        );
    }

    protected function createOpenaiDriver(): AiProvider
    {
        return new OpenAiProvider(
            (array) $this->config->get('ai.providers.openai', []),
            (int) $this->config->get('ai.timeout', 60),
            (int) $this->config->get('ai.retries', 2),
        );
    }
}
