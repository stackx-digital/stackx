<?php

namespace App\Support\Facades;

use App\Services\Ai\AiManager;
use App\Services\Ai\Contracts\AiProvider;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array structuredJson(string $system, string $user, array $options = [])
 * @method static string name()
 * @method static string model()
 * @method static AiProvider driver(string|null $driver = null)
 *
 * @see AiManager
 */
class Ai extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AiManager::class;
    }
}
