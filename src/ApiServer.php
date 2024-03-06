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
    public $headers = [];

    /**
     * The number of seconds to wait for a response.
     *
     * @var int|null
     */
    public $timeout = null;

    /**
     * The list of simple actions for this API.
     *
     * @var array{string:string}|array{}
     */
    public $actions = [];

    /**
     * Actions and methods to wrap into a custom response class.
     *
     * @var array
     */
    public $responses = [];

    /**
     * Returns the API base URL.
     *
     * @return string
     */
    abstract public function getBaseUrl();

    /**
     * Modify a pristine new Pending Request.
     *
     * @param  \Illuminate\Http\Client\PendingRequest  $request
     * @return \Illuminate\Http\Client\PendingRequest|null|void
     */
    public function beforeBuild(PendingRequest $request)
    {
        return $this->build($request);
    }

    /**
     * Modify Pending Request after its bootstrapped.
     *
     * @param  \Illuminate\Http\Client\PendingRequest  $request
     * @return \Illuminate\Http\Client\PendingRequest|null|void
     */
    public function afterBuild(PendingRequest $request)
    {
        //
    }

    /**
     * Build the pending request for this API.
     *
     * @deprecated Use `afterBuild()` instead.
     *
     * @param  \Illuminate\Http\Client\PendingRequest  $request
     * @return \Illuminate\Http\Client\PendingRequest|null|void
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
