<?php

namespace Laragear\ApiManager;

use ErrorException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use LogicException;
use ReflectionMethod;
use ReflectionProperty;
use function array_merge;
use function array_unshift;
use function class_basename;
use function explode;
use function method_exists;
use function sprintf;
use function str_contains;

/**
 * @template TValue
 *
 * @mixin \Laragear\ApiManager\ApiServer
 * @mixin \Illuminate\Http\Client\PendingRequest
 * @mixin TValue
 */
class ApiRequestProxy
{
    use ForwardsCalls;

    /**
     * The built Pending Request.
     *
     * @var \Illuminate\Http\Client\PendingRequest
     */
    public PendingRequest $request;

    /**
     * Create a new Api Request instance.
     *
     * @param  \Laragear\ApiManager\ApiServer  $api
     * @param  \Illuminate\Http\Client\Factory  $requestFactory
     * @param  string  $name
     */
    public function __construct(protected Factory $requestFactory, public ApiServer $api, public string $name)
    {
        if (!$this->api->getBaseUrl()) {
            // @codeCoverageIgnoreStart
            throw new LogicException('There is no base URL for this ['.class_basename($api).'] API.');
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Builds the API request once.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function buildApiRequest(): PendingRequest
    {
        return $this->request ??= tap(
            $this->requestFactory->baseUrl($this->api->getBaseUrl())
                ->when($this->api->headers)->withHeaders($this->api->headers)
                ->when($this->api->timeout)->timeout($this->api->timeout)
                ->when(
                    $this->api->authBasic(),
                    static function (PendingRequest $request, array $auth): PendingRequest {
                        return $request->withBasicAuth(...$auth);
                    },
                )
                ->when(
                    $this->api->authDigest(),
                    static function (PendingRequest $request, array $auth): PendingRequest {
                        return $request->withDigestAuth(...$auth);
                    },
                )
                ->when(
                    $this->api->authToken(),
                    static function (PendingRequest $request, string $auth): PendingRequest {
                        return $request->withToken($auth);
                    },
                ),
            [$this->api, 'build'],
        );
    }

    /**
     * Sets the request to use a given pool.
     *
     * @param  \Illuminate\Http\Client\Pool  $pool
     * @param  string|null  $as
     * @return $this
     * @throws \ReflectionException
     */
    public function on(Pool $pool, string $as = null): static
    {
        // We will have to retrieve by force the pool values.
        $handler = (new ReflectionProperty($pool, 'handler'))->getValue($pool);
        $requests = (new ReflectionProperty($pool, 'pool'));

        $request = $this->buildApiRequest()->setHandler($handler)->async();

        // If it's using a name, set it here.
        $value = $as ? [$as => $request] : [$request];

        $requests->setValue($pool, array_merge($requests->getValue($pool), $value));

        return $this;
    }

    /**
     * Finds an action string based on its name.
     *
     * @param  string  $name
     * @return string|null
     * @internal
     */
    protected function findApiAction(string $name): ?string
    {
        return $this->api->actions[$name] ?? (function ($name) {
            foreach ($this->api->actions as $index => $action) {
                if ($name === Str::camel($index)) {
                    return $action;
                }
            }
            return null;
        })($name);
    }

    /**
     * Executes a pre-defined short action.
     *
     * @param  string  $action
     * @param  array  $parameters
     * @return \Illuminate\Http\Client\PendingRequest|\Illuminate\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface
     */
    protected function executeApiAction(string $action, array $parameters): PendingRequest|Response|PromiseInterface {
        [$verb, $path] = str_contains($action, ':') ? explode(':', $action) : ['get', $action];

        return $this->buildApiRequest()->{$verb}($path, ...$parameters);
    }

    /**
     * Executes the API class method, optionally passing the request if needed.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     * @throws \ReflectionException
     */
    protected function executeApiMethod(string $method, array $parameters): mixed
    {
        $paramType = Arr::first((new ReflectionMethod($this->api, $method))->getParameters())?->getType()?->getName();

        // If the first parameter requires the Pending Request, add it.
        if ($paramType === PendingRequest::class) {
            array_unshift($parameters, $this->buildApiRequest());
        }

        return $this->forwardDecoratedCallTo($this->api, $method, $parameters);
    }

    /**
     * Proxy accessing an attribute onto the API instance.
     *
     * @param  string  $name
     * @return $this|\GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\PendingRequest|\Illuminate\Http\Client\Response
     * @throws \ErrorException|\ReflectionException
     */
    public function __get(string $name)
    {
        // If the method exists in the API class, pass it to it.
        if (method_exists($this->api, $name)) {
            return $this->executeApiMethod($name, []);
        }

        // If not, try to find the action name and build it.
        if ($action = $this->findApiAction($name)) {
            return $this->executeApiAction($action, []);
        }

        throw new ErrorException(sprintf('Undefined property: %s::$%s', $this->api::class, $name));
    }

    /**
     * Handle dynamic calls to the object.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return static|\GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\PendingRequest|\Illuminate\Http\Client\Response
     * @throws \ReflectionException
     */
    public function __call(string $method, array $parameters)
    {
        // If the method exists in the API class, pass it to it.
        if (method_exists($this->api, $method)) {
            return $this->executeApiMethod($method, $parameters);
        }

        // If not, try to find the action name and build it.
        if ($action = $this->findApiAction($method)) {
            return $this->executeApiAction($action, $parameters);
        }

        // Just forward it to the request instance.
        return $this->forwardDecoratedCallTo($this->buildApiRequest(), $method, $parameters);
    }
}
