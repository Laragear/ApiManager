<?php

namespace Laragear\ApiManager;

use Illuminate\Http\Client\PendingRequest;
use JetBrains\PhpStorm\ArrayShape;

abstract class ApiServer
{
    /**
     * The headers to include in each request.
     *
     * @var array{string:string}
     */
    #[ArrayShape(["string", "string"])]
    public array $headers = [];

    /**
     * The number of seconds to wait for a response.
     *
     * @var int|null
     */
    public ?int $timeout = null;

    /**
     * The list of simple actions for this API.
     *
     * @var array{string:string}
     */
    #[ArrayShape(["string", "string"])]
    public array $actions = [];

    /**
     * Returns the API base URL.
     *
     * @return string
     */
    abstract public function getBaseUrl(): string;

    /**
     * Build the pending request for this API.
     *
     * @param  \Illuminate\Http\Client\PendingRequest  $request
     * @return \Illuminate\Http\Client\PendingRequest|void
     */
    public function build(PendingRequest $request)
    {
        //
    }

    /**
     * Returns the Basic credentials array for authentication.
     *
     * @example ["john@doe.com", "my secret"]
     *
     * @return array{string,string}|void
     */
    #[ArrayShape(["string", "string"])]
    public function authBasic()
    {
        //
    }

    /**
     * Returns the Digest credentials array for authentication.
     *
     * @example ["john@doe.com", "my secret"]
     *
     * @return array{string,string}|void
     */
    #[ArrayShape(["string", "string"])]
    public function authDigest()
    {
        //
    }

    /**
     * Returns the Bearer Token used for authentication.
     *
     * @return string|void
     */
    public function authToken()
    {
        //
    }

    /**
     * Registers the current API Server in the API Manager.
     *
     * @param  string|null  $name
     * @return void
     */
    public static function registerInApiManager(string $name = null): void
    {
        app(ApiManager::class)->register(static::class, $name);
    }

    /**
     * Returns the API Server implementation.
     *
     * @param  array  $parameters
     * @return \Laragear\ApiManager\ApiRequestProxy<static>
     */
    public static function api(array $parameters = []): ApiRequestProxy
    {
        return app(ApiManager::class)->server($parameters);
    }
}
