<?php

namespace Tests;

use Laragear\ApiManager\ApiServer;
use Laragear\ApiManager\Facades\Api as ApiFacade;

class ApiServerTest extends TestCase
{
    public function test_registers_itself(): void
    {
        TestSelfRegistrableApiServer::registerInApiManager();

        static::assertTrue(ApiFacade::hasServer('testSelfRegistrableApiServer'));
    }

    public function test_registers_itself_with_name(): void
    {
        TestSelfRegistrableApiServer::registerInApiManager('custom');

        static::assertSame('custom', ApiFacade::server('custom')->name);
    }
}

class TestSelfRegistrableApiServer extends ApiServer
{
    public function getBaseUrl(): string
    {
        return  'dummy';
    }
}
