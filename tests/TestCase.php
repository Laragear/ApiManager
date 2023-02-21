<?php

namespace Tests;

use Laragear\ApiManager\ApiManagerServiceProvider;
use Laragear\ApiManager\Facades\Api;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ApiManagerServiceProvider::class,
        ];
    }
}
