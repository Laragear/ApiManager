<?php

namespace Tests\Console\Commands;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MakeApiTest extends TestCase
{
    protected function setUp(): void
    {
        $deleteFiles = function (): void {
            $this->app->make('files')->deleteDirectory($this->app->path('Http/Apis'));
            $this->app->make('files')->deleteDirectory($this->app->basePath('stubs'));
        };

        $this->afterApplicationCreated($deleteFiles);
        $this->beforeApplicationDestroyed($deleteFiles);

        parent::setUp();
    }

    #[Test]
    public function creates_api_from_stub(): void
    {
        $this->artisan('make:api TestApi')->assertSuccessful();

        static::assertFileEquals($this->app->path('Http/Apis/TestApi.php'), __DIR__.'/stubs/test-api-test-api.stub');
    }

    #[Test]
    public function asks_for_missing_input(): void
    {
        $this->artisan('make:api')
            ->expectsQuestion('What should the api server be named?', 'TestApi')
            ->assertSuccessful();

        static::assertFileEquals($this->app->path('Http/Apis/TestApi.php'), __DIR__.'/stubs/test-api-test-api.stub');
    }

    #[Test]
    public function allows_overriding_api_stub(): void
    {
        $files = $this->app->make('files');
        $files->ensureDirectoryExists($this->app->basePath('stubs'));
        $files->put($this->app->basePath('stubs/api.stub'), 'DummyClass');

        $this->artisan('make:api FooBar')->assertSuccessful();

        static::assertSame('FooBar', $files->get($this->app->path('Http/Apis/FooBar.php')));
    }
}
