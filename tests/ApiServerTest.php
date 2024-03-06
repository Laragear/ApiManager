<?php

namespace Tests;

use Laragear\ApiManager\ApiServer;
use PHPUnit\Framework\Attributes\Test;

class ApiServerTest extends TestCase
{
    #[Test]
    public function builds_itself(): void
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
