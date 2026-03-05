<?php

namespace Laragear\ApiManager;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Tappable;

use function app;

abstract class ApiServer
{
    use Conditionable;
    use Tappable;

    /**
     * The headers to include in each request.
     *
     * @var array{string:string}|array
     */
    public array $headers = [];

    /**
     * The number of seconds to wait for a response.
     */
    public ?int $timeout = null;

    /**
     * The list of simple actions for this API.
     *
     * @var array{string:string}|array{}
     *
     * @deprecated Use the `\Laragear\ApiManager\Attributes\ApiActions` attributes in the class instead.
     */
    public array $actions = [];

    /**
     * Actions and methods to wrap into a custom response class.
     *
     * @deprecated Use the `\Laragear\ApiManager\Attributes\Response` attribute in the target method instead.
     */
    public array $responses = [];

    /**
     * Returns the API base URL.
     */
    abstract public function getBaseUrl(): string;

    /**
     * Modify a pristine new Pending Request.
     *
     * @return \Illuminate\Http\Client\PendingRequest|null|void
     */
    public function beforeBuild(PendingRequest $request)
    {
        //
    }

    /**
     * Modify Pending Request after its bootstrapped.
     *
     * @return \Illuminate\Http\Client\PendingRequest|null|void
     */
    public function afterBuild(PendingRequest $request)
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
     * Returns the API Server implementation instance.
     *
     * @return \Laragear\ApiManager\ApiRequestProxy<static>
     */
    public static function api(array $parameters = []): ApiRequestProxy
    {
        $proxy = app(ApiRequestProxy::class, ['api' => app(static::class)]);

        if ($parameters) {
            $proxy->withUrlParameters($parameters);
        }

        return $proxy;
    }
}
