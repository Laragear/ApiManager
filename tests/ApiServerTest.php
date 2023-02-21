<?php

namespace Tests;

use Laragear\ApiManager\ApiServer;

class ApiServerTest extends TestCase
{
    public function test_builds_itself(): void
    {
        static::assertInstanceOf(TestSelfRegistrableApiServer::class, TestSelfRegistrableApiServer::api()->api);
    }

}

class TestSelfRegistrableApiServer extends ApiServer
{
    public function getBaseUrl(): string
    {
        return  'dummy';
    }
}
