<?php

namespace Tests;

use Illuminate\Support\Fluent;
use Laragear\ApiManager\ApiRequestProxy;
use Laragear\ApiManager\ApiServer;
use Laragear\ApiManager\Facades\Api;
use LogicException;

class ApiManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TestDummyApiServer::registerInApiManager('test');
    }

    public function test_defines_server_using_class_base_name(): void
    {
        Api::register(TestDummyApiServer::class);

        static::assertTrue(Api::hasServer('testDummyApiServer'));
    }

    public function test_defines_server_using_custom_name(): void
    {
        Api::register(TestDummyApiServer::class, 'anything');

        static::assertTrue(Api::hasServer('anything'));
    }

    public function test_retrieves_server_by_name(): void
    {
        $server = Api::server('test');

        static::assertInstanceOf(ApiRequestProxy::class, $server);
        static::assertSame('test', $server->name);
        static::assertInstanceOf(TestDummyApiServer::class, $server->api);
    }

    public function test_retrieves_server_using_dynamic_camelcase_method(): void
    {
        Api::register(TestDummyApiServer::class);

        $server = Api::testDummyApiServer();

        static::assertInstanceOf(ApiRequestProxy::class, $server);
        static::assertSame('testDummyApiServer', $server->name);
        static::assertInstanceOf(TestDummyApiServer::class, $server->api);
    }

    public function test_throws_when_server_doesnt_exist(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [invalid] server is not registered.');

        Api::server('invalid');
    }

    public function test_wraps_api_into_proxy(): void
    {
        static::assertInstanceOf(ApiRequestProxy::class, Api::server('test'));
    }

    public function test_resolves_server_using_dependency_injection(): void
    {
        static::assertInstanceOf(Fluent::class, Api::server('test')->api->fluent);
    }
}

class TestDummyApiServer extends ApiServer
{
    public function getBaseUrl(): string
    {
        return  'http://www.test.com';
    }

    public function __construct(public Fluent $fluent)
    {

    }
}
