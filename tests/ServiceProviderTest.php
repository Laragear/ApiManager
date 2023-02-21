<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Laragear\ApiManager\ApiManager;

class ServiceProviderTest extends TestCase
{
    public function test_registers_preload(): void
    {

        static::assertTrue($this->app->has(ApiManager::class));
    }

    public function test_registers_command(): void
    {
        static::assertArrayHasKey('make:api', Artisan::all());
    }
}
