<?php

namespace Laragear\ApiManager;

use Illuminate\Support\ServiceProvider;

class ApiManagerServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(Console\Commands\Api::class);
        }
    }
}
