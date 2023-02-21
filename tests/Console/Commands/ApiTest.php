<?php

namespace Tests\Console\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ApiTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory($this->app->path('Http/Apis'));
        File::deleteDirectory($this->app->basePath('stubs'));

        parent::tearDown();
    }

    public function test_creates_api_from_stub(): void
    {
        $this->artisan('make:api Example')->assertSuccessful();

        static::assertFileEquals($this->app->path('Http/Apis/Example.php'), __DIR__.'/example.stub');
    }

    public function test_uses_snake_case_for_api_stub_generation(): void
    {
        $this->artisan('make:api FooBar')->assertSuccessful();

        static::assertFileEquals($this->app->path('Http/Apis/FooBar.php'), __DIR__.'/foo-bar.stub');
    }

    public function test_allows_overriding_api_stub(): void
    {
        File::ensureDirectoryExists($this->app->basePath('stubs'));
        File::copy(__DIR__.'/custom.stub', $this->app->basePath('stubs/api.stub'));

        $this->artisan('make:api FooBar')->assertSuccessful();

        static::assertFileEquals(__DIR__.'/custom.stub', $this->app->path('Http/Apis/FooBar.php'));
    }
}
