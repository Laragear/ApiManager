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
use function array_splice;
use function class_basename;
use function explode;
use function method_exists;
use function sprintf;
use function str_contains;

/**
 * @template TValue of \Laragear\ApiManager\ApiServer
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
     */
    public PendingRequest $request;

    /**
     * Create a new Api Request instance.
     */
    public function __construct(protected Factory $requestFactory, public ApiServer $api)
    {
        if (!$this->api->getBaseUrl()) {
            throw new LogicException('There is no base URL for this ['.class_basename($api).'] API.');
        }
    }

    /**
     * Builds the API request once.
     */
    protected function getApiRequest(): PendingRequest
    {
        return $this->request ??= $this->createRequest();
    }

    /**
     * Creates a new request.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function createRequest(): PendingRequest
    {
        /** @var \Illuminate\Http\Client\PendingRequest $request */
        $request = $this->requestFactory->baseUrl($this->api->getBaseUrl());

        $request = ($this->api->beforeBuild($request) ?? $request)
            ->when($this->api->headers)->withHeaders($this->api->headers)
            ->when($this->api->timeout)->timeout($this->api->timeout)
            ->when(
                $this->api->authBasic(),
                static function (PendingRequest $request, array $auth): PendingRequest {
                    return $request->withBasicAuth(...$auth);
                }
            )
            ->when(
                $this->api->authDigest(),
                static function (PendingRequest $request, array $auth): PendingRequest {
                    return $request->withDigestAuth(...$auth);
                }
            )
            ->when(
                $this->api->authToken(),
                static function (PendingRequest $request, array|string $auth): PendingRequest {
                    return $request->withToken(...(array) $auth);
                }
            );

        return $this->api->afterBuild($request) ?? $request;
    }

    /**
     * Sets the request to use a given pool.
     *
     * @return $this
     */
    public function on(Pool $pool, ?string $as = null): static
    {
        // We will have to retrieve by force the pool values.
        // @phpstan-ignore-next-line
        $handler = tap(new ReflectionProperty($pool, 'handler'))->setAccessible(true)->getValue($pool);
        $requests = (new ReflectionProperty($pool, 'pool'));

        $request = $this->getApiRequest()->setHandler($handler)->async();

        // If it's using a name, set it here.
        $value = $as ? [$as => $request] : [$request];

        // @phpstan-ignore-next-line
        $requests->setValue($pool, array_merge(tap($requests)->setAccessible(true)->getValue($pool), $value));

        return $this;
    }

    /**
     * Finds an action string based on its name.
     *
     * @internal
     */
    protected function findApiAction(string $name): ?string
    {
        if (isset($this->api->actions[$name])) {
            return $this->api->actions[$name];
        }

        foreach ($this->api->actions as $index => $action) {
            if ($name === Str::camel($index)) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Executes the API class method, optionally passing the request if needed.
     */
    protected function executeApiMethod(string $name, string $method, array $parameters): mixed
    {
        // If any parameter requires the Pending Request, add it and stop checking the rest.
        foreach ((new ReflectionMethod($this->api, $method))->getParameters() as $key => $parameter) {
            if ($parameter->getType()?->getName() === PendingRequest::class) { // @phpstan-ignore-line
                array_splice($parameters, $key, 0, [$this->getApiRequest()]);

                break;
            }
        }

        return $this->wrapResponse($this->forwardDecoratedCallTo($this->api, $method, $parameters), $name);
    }

    /**
     * Executes a pre-defined short action.
     */
    protected function executeApiAction(string $name, string $action, array $parameters): mixed
    {
        [$verb, $path] = str_contains($action, ':') ? explode(':', $action) : ['get', $action];

        return $this->wrapResponse($this->getApiRequest()->{$verb}($path, ...$parameters), $name);
    }

    /**
     * Wrap the response or promise into a custom response if found.
     *
     * @param mixed  $response
     * @param string $name
     * @return mixed
     */
    protected function wrapResponse(mixed $response, string $name): mixed
    {
        if ($name = $this->findClassResponse($name)) {
            if ($response instanceof Response) {
                $response = new $name($response->toPsrResponse());
            } else {
                if ($response instanceof PromiseInterface) {
                    $response = $response->then(function (mixed $response) use ($name) {
                        return $response instanceof Response ? new $name($response->toPsrResponse()) : $response;
                    });
                }
            }
        }

        return $response;
    }

    /**
     * Retrieves the class Response for this action.
     *
     * @return class-string<\Illuminate\Http\Client\Response>|null
     */
    protected function findClassResponse(string $action): ?string
    {
        return Arr::get($this->api->responses, Str::camel($action));
    }

    /**
     * Proxy accessing an attribute onto the API instance.
     */
    public function __get(string $name): mixed
    {
        // If the method exists in the API class, pass it to it.
        if (method_exists($this->api, $name)) {
            return $this->executeApiMethod($name, $name, []);
        }

        // If not, try to find the action name and build it.
        if ($action = $this->findApiAction($name)) {
            return $this->executeApiAction($name, $action, []);
        }

        throw new ErrorException(sprintf('Undefined property: %s::$%s', $this->api::class, $name));
    }

    /**
     * Handle dynamic calls to the object.
     */
    public function __call(string $method, array $parameters): mixed
    {
        // If the method exists in the API class, pass it to it.
        if (method_exists($this->api, $method)) {
            return $this->executeApiMethod($method, $method, $parameters);
        }

        // If not, try to find the action name and build it.
        if ($action = $this->findApiAction($method)) {
            return $this->executeApiAction($method, $action, $parameters);
        }

        // Just forward it to the request instance.
        return $this->forwardDecoratedCallTo($this->getApiRequest(), $method, $parameters);
    }
}
