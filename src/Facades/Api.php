<?php

namespace Laragear\ApiManager\Facades;

use Illuminate\Support\Facades\Facade;
use Laragear\ApiManager\ApiManager;

/**
 * @method static void register(string $api, string $name = null)
 * @method static bool hasServer(string $name)
 * @method static \Laragear\ApiManager\ApiRequestProxy server(string $name, array $parameters = [])
 */
class Api extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string|class-string
     */
    protected static function getFacadeAccessor(): string
    {
        return ApiManager::class;
    }
}
