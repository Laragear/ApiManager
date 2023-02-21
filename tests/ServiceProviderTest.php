<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;

class ServiceProviderTest extends TestCase
{
    public function test_registers_command(): void
    {
        static::assertArrayHasKey('make:api', Artisan::all());
    }
}
