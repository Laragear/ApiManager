<?php

namespace Tests\Console\Commands;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MakeApiResponseTest extends TestCase
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
    public function creates_api_response_from_stub_and_asks_to_create_api_server(): void
    {
        $this->artisan('make:api-response TestApi ViewResponse')
            ->expectsConfirmation("The API Server [TestApi] doesn't exists. Do you want to create it?", 'yes')
            ->assertSuccessful();

        static::assertFileExists($this->app->path('Http/Apis/TestApi.php'));
        static::assertFileExists($this->app->path('Http/Apis/TestApi/Responses/ViewResponse.php'));

        static::assertFileEquals(
            $this->app->path('Http/Apis/TestApi.php'),
            __DIR__.'/stubs/test-api-test-api.stub'
        );

        static::assertFileEquals(
            $this->app->path('Http/Apis/TestApi/Responses/ViewResponse.php'),
            __DIR__.'/stubs/test-api-response-view-response.stub'
        );
    }

    #[Test]
    public function creates_api_response_from_stub_without_creating_api_server(): void
    {
        $this->artisan('make:api-response TestApi ViewResponse')
            ->expectsConfirmation("The API Server [TestApi] doesn't exists. Do you want to create it?", false)
            ->assertSuccessful();

        static::assertFileDoesNotExist($this->app->path('Http/Apis/TestApi.php'));
        static::assertFileExists($this->app->path('Http/Apis/TestApi/Responses/ViewResponse.php'));

        static::assertFileEquals(
            $this->app->path('Http/Apis/TestApi/Responses/ViewResponse.php'),
            __DIR__.'/stubs/test-api-response-view-response.stub'
        );
    }

    #[Test]
    public function creates_api_response_from_stub_without_creating_existing_api_server(): void
    {
        $this->app->make('files')->ensureDirectoryExists($this->app->path('Http/Apis'));
        $this->app->make('files')->put($this->app->path('Http/Apis/TestApi.php'), '');

        $this->artisan('make:api-response TestApi ViewResponse')->assertSuccessful();

        static::assertFileExists($this->app->path('Http/Apis/TestApi/Responses/ViewResponse.php'));

        static::assertFileEquals(
            $this->app->path('Http/Apis/TestApi/Responses/ViewResponse.php'),
            __DIR__.'/stubs/test-api-response-view-response.stub'
        );
    }

    #[Test]
    public function asks_for_missing_input(): void
    {
        $this->app->make('files')->ensureDirectoryExists($this->app->path('Http/Apis'));
        $this->app->make('files')->put($this->app->path('Http/Apis/TestApi.php'), '');

        $this->artisan('make:api-response')
            ->expectsQuestion('What should the api server be named?', 'TestApi')
            ->expectsQuestion('What should the api response be named?', 'ViewResponse')
            ->assertSuccessful();

        static::assertFileExists($this->app->path('Http/Apis/TestApi/Responses/ViewResponse.php'));

        static::assertFileEquals(
            $this->app->path('Http/Apis/TestApi/Responses/ViewResponse.php'),
            __DIR__.'/stubs/test-api-response-view-response.stub'
        );
    }

    #[Test]
    public function allows_overriding_stub(): void
    {
        $files = $this->app->make('files');
        $files->ensureDirectoryExists($this->app->basePath('stubs'));
        $files->put($this->app->basePath('stubs/api-response.stub'), 'DummyClass');
        $files->ensureDirectoryExists($this->app->path('Http/Apis'));
        $files->put($this->app->path('Http/Apis/TestApi.php'), '');

        $this->artisan('make:api-response TestApi FooBar')->assertSuccessful();

        static::assertSame('FooBar', $files->get($this->app->path('Http/Apis/TestApi/Responses/FooBar.php')));
    }
}
