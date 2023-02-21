<?php

namespace Laragear\ApiManager;

use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Support\Str;
use InvalidArgumentException;
use function class_basename;

class ApiManager
{
    /**
     * The defined APIs.
     *
     * @var array|string[]|class-string[]
     */
    protected array $apis = [];

    /**
     * Create a new API Manager instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     */
    public function __construct(protected ContainerContract $container)
    {
        //
    }

    /**
     * Define a new API using the class and an optional name.
     *
     * @param  string|class-string  $api
     * @param  string|null  $name
     * @return void
     */
    public function register(string $api, string $name = null): void
    {
        $this->apis[$name ?? Str::camel(class_basename($api))] = $api;
    }

    /**
     * Returns a new API server.
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return \Laragear\ApiManager\ApiRequestProxy
     */
    public function server(string $name, array $parameters = []): ApiRequestProxy
    {
        return $this->hasServer($name)
            ? $this->buildApi($this->apis[$name], $name, $parameters)
            : throw new InvalidArgumentException("The [$name] server is not registered.");
    }

    /**
     * Determines if an API has been registered.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasServer(string $name): bool
    {
        return isset($this->apis[$name]);
    }

    /**
     * Builds an API object.
     *
     * @param  string|class-string  $class
     * @param  string  $name
     * @return \Laragear\ApiManager\ApiRequestProxy
     */
    protected function buildApi(string $class, string $name, array $parameters): ApiRequestProxy
    {
        $proxy = $this->container->make(ApiRequestProxy::class, [
            'api' => $this->container->make($class),
            'name' => $name
        ]);

        if ($parameters) {
            $proxy->withUrlParameters($parameters);
        }

        return $proxy;
    }

    /**
     * Handle dynamic calls to the registered servers.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Laragear\ApiManager\ApiRequestProxy
     */
    public function __call(string $method, array $parameters)
    {
        return $this->server($method, $parameters);
    }
}
