<?php

namespace Laragear\ApiManager;

use Illuminate\Support\ServiceProvider;

/**
 * @internal
 */
class ApiManagerServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->commands([
            Console\Commands\MakeApi::class,
            Console\Commands\MakeApiResponse::class,
        ]);
    }
}
