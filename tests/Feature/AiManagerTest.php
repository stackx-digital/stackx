<?php

namespace Tests\Feature;

use App\Services\Ai\AiManager;
use App\Services\Ai\Exceptions\AiException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiManagerTest extends TestCase
{
    private function manager(): AiManager
    {
        return app(AiManager::class);
    }

    public function test_default_driver_follows_config(): void
    {
        Config::set('ai.default', 'anthropic');
        Config::set('ai.providers.anthropic.key', 'test-key');

        $this->assertSame('anthropic', $this->manager()->driver()->name());
        $this->assertSame('openai', $this->manager()->driver('openai')->name());
    }

    public function test_anthropic_driver_parses_structured_json(): void
    {
        Config::set('ai.providers.anthropic.key', 'test-key');

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => '{"angle":"savings","hook_type":"question"}']],
            ]),
        ]);

        $result = $this->manager()->driver('anthropic')
            ->structuredJson('tag this ad', 'Ad: Raya promo');

        $this->assertSame('savings', $result['angle']);
        $this->assertSame('question', $result['hook_type']);
    }

    public function test_openai_driver_parses_structured_json(): void
    {
        Config::set('ai.providers.openai.key', 'test-key');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"angle":"urgency"}']]],
            ]),
        ]);

        $result = $this->manager()->driver('openai')
            ->structuredJson('tag this ad', 'Ad: flash sale');

        $this->assertSame('urgency', $result['angle']);
    }

    public function test_code_fenced_json_is_tolerated(): void
    {
        Config::set('ai.providers.anthropic.key', 'test-key');

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => "```json\n{\"ok\":true}\n```"]],
            ]),
        ]);

        $result = $this->manager()->driver('anthropic')
            ->structuredJson('sys', 'user');

        $this->assertTrue($result['ok']);
    }

    public function test_missing_api_key_throws(): void
    {
        Config::set('ai.providers.anthropic.key', null);

        $this->expectException(AiException::class);

        $this->manager()->driver('anthropic')->structuredJson('sys', 'user');
    }
}
