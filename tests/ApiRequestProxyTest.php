<?php

namespace Tests;

use BadMethodCallException;
use ErrorException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laragear\ApiManager\ApiServer;
use LogicException;

class ApiRequestProxyTest extends TestCase
{
    public function test_throws_when_api_has_empty_base_url(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('There is no base URL for this [TestEmptyApiUrlServer] API.');

        TestEmptyApiUrlServer::api();
    }

    public function test_use_api_properties_to_build_request(): void
    {
        Http::fake();

        TestPropertiesApiServer::api()
            ->beforeSending(function (Request $request, $options) {
                static::assertSame(10, $options['timeout']);
            })
            ->get('example');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('https://www.properties.com/example', $request->url());
            static::assertTrue($request->hasHeader('Host', ['www.properties.com']));
            static::assertTrue($request->hasHeader('X-Foo', ['bar']));

            return true;
        });
    }

    public function test_builds_default_request(): void
    {
        Http::fake();

        TestActionApiServer::api()->get('example');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('https://www.test.com/example', $request->url());
            static::assertTrue($request->hasHeader('Host', ['www.test.com']));
            static::assertCount(2, $request->headers());

            return true;
        });
    }

    public function test_builds_custom_request(): void
    {
        Http::fake();

        TestBuildApiServer::api()->get('example');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('https://www.not-test.com/example', $request->url());
            static::assertTrue($request->hasHeader('Host', ['www.not-test.com']));
            static::assertCount(2, $request->headers());

            return true;
        });
    }

    public function test_builds_on_inline_action_get(): void
    {
        Http::fake();

        TestActionApiServer::api()->foo();

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('GET', $request->method());
            static::assertSame('https://www.test.com/foo/action', $request->url());
            return true;
        });
    }

    public function test_builds_on_inline_action_with_get_verb(): void
    {
        Http::fake();

        TestActionApiServer::api()->bar();

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('GET', $request->method());
            static::assertSame('https://www.test.com/bar/action', $request->url());
            return true;
        });
    }

    public function test_builds_on_inline_action_using_camel_case(): void
    {
        Http::fake();

        TestActionApiServer::api()->bazQuz();

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('https://www.test.com/baz/quz', $request->url());
            return true;
        });
    }

    public function test_builds_on_inline_action_using_http_verb(): void
    {
        Http::fake();

        TestActionApiServer::api()->bazQuz();

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('POST', $request->method());
            static::assertSame('https://www.test.com/baz/quz', $request->url());
            return true;
        });
    }

    public function test_builds_on_inline_action_with_url_parameters(): void
    {
        Http::fake();

        TestActionApiServer::api(['id' => 10])->parameter();

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('POST', $request->method());
            static::assertSame('https://www.test.com/baz/quz/10', $request->url());
            return true;
        });
    }

    public function test_builds_on_inline_action_with_hacky_verb_and_path(): void
    {
        Http::fake();

        $request = TestActionApiServer::api()->hacky();

        $request->get('test');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('GET', $request->method());
            static::assertSame('www.google.com/test', $request->url());
            return true;
        });
    }

    public function test_throws_when_method_doesnt_exist_in_pending_request(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method Illuminate\Http\Client\PendingRequest::invalid does not exist.');

        TestActionApiServer::api()->invalid();
    }

    public function test_builds_on_action_method_with_parameters(): void
    {
        Http::fake();

        $request = TestActionApiServer::api()->override('test');

        static::assertSame('test', $request);

        Http::assertNothingSent();
    }

    public function test_forwards_calls_to_the_request(): void
    {
        Http::fake();

        TestActionApiServer::api()->setBaseUrl('www.google.com')->get('test');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('GET', $request->method());
            static::assertSame('www.google.com/test', $request->url());
            return true;
        });
    }

    public function test_forwards_properties_to_api_server_action(): void
    {
        Http::fake();

        $result = TestActionApiServer::api()->asProperty;

        static::assertSame('as property', $result);
    }

    public function test_throws_when_property_does_not_exist_in_api(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined property: Tests\TestActionApiServer::$badProperty');

        TestActionApiServer::api()->badProperty;
    }

    public function test_forwards_properties_to_api_server_inline_action(): void
    {
        Http::fake();

        TestActionApiServer::api()->foo;

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('GET', $request->method());
            static::assertSame('https://www.test.com/foo/action', $request->url());
            return true;
        });
    }

    public function test_uses_auth_basic(): void
    {
        Http::fake();

        TestAuthApiServer::api()->useAuth('basic')->get('test');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame(['Basic Zm9vOmJhcg=='], $request->header('Authorization'));

            return true;
        });
    }

    public function test_uses_auth_digest(): void
    {
        Http::fake();

        TestAuthApiServer::api()->useAuth('digest')->beforeSending(static function (Request $request, array $options): void {
            static::assertSame(['baz', 'quz', 'digest'], $options['auth']);
        })->get('test');

        Http::assertSentCount(1);
    }

    public function test_uses_auth_token(): void
    {
        Http::fake();

        TestAuthApiServer::api()->useAuth('token')->get('test');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame(['Bearer qux'], $request->header('Authorization'));

            return true;
        });
    }

    public function test_support_pools(): void
    {
        Http::fake([
            'https://www.test.com/200' => Http::response('', 200),
            'https://www.test.com/400' => Http::response('', 400),
            'https://www.test.com/500' => Http::response('', 500),
        ]);

        $responses = Http::pool(static fn (Pool $pool): array => [
            TestActionApiServer::api()->on($pool)->get('200'),
            TestActionApiServer::api()->on($pool)->get('400'),
            TestActionApiServer::api()->on($pool)->get('500'),
        ]);

        static::assertSame(200, $responses[0]->status());
        static::assertSame(400, $responses[1]->status());
        static::assertSame(500, $responses[2]->status());
    }

    public function test_support_pools_with_named_requests(): void
    {
        Http::fake([
            'https://www.test.com/200' => Http::response('', 200),
            'https://www.test.com/400' => Http::response('', 400),
            'https://www.test.com/500' => Http::response('', 500),
        ]);

        $responses = Http::pool(static fn (Pool $pool): array => [
            TestActionApiServer::api()->on($pool, 'foo')->get('200'),
            TestActionApiServer::api()->on($pool, 'bar')->get('400'),
            TestActionApiServer::api()->on($pool, 'quz')->get('500'),
        ]);

        static::assertSame(200, $responses['foo']->status());
        static::assertSame(400, $responses['bar']->status());
        static::assertSame(500, $responses['quz']->status());
    }
}

class TestPropertiesApiServer extends ApiServer
{
    public function getBaseUrl(): string
    {
        return  'https://www.properties.com';
    }

    public array $headers = ['X-Foo' => 'bar'];

    public ?int $timeout = 10;
}

class TestActionApiServer extends ApiServer
{
    public $url;

    public function setBaseUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    public function getBaseUrl(): string
    {
        return $this->url ?? 'https://www.test.com';
    }

    public array $actions = [
        'foo' => 'foo/action',
        'bar' => 'get:bar/action',
        'baz quz' => 'post:baz/quz',
        'parameter' => 'post:baz/quz/{id}',
        'invalid' => 'invalid:/something',
        'hacky' => 'baseUrl:www.google.com',
        'override' => 'get:/not-overridden',
        'as property' => 'get:/not-overridden-property'
    ];

    public function override(PendingRequest $request, string $message)
    {
        return $message;
    }

    public function asProperty()
    {
        return 'as property';
    }
}

class TestEmptyApiUrlServer extends ApiServer
{
    public function getBaseUrl(): string
    {
        return  '';
    }
}

class TestBuildApiServer extends TestActionApiServer
{
    public function getBaseUrl(): string
    {
        return 'https://www.not-test.com/example';
    }

    public function build(PendingRequest $request): PendingRequest
    {
        return $request->baseUrl('https://www.not-test.com');
    }
}

class TestAuthApiServer extends TestActionApiServer
{
    public string $auth = '';

    public function useAuth(string $auth): static
    {
        $this->auth = $auth;

        return $this;
    }

    public function authBasic()
    {
        return $this->auth === 'basic' ? ['foo', 'bar'] : parent::authBasic();
    }

    public function authDigest()
    {
        return $this->auth === 'digest' ? ['baz', 'quz'] : parent::authDigest();
    }

    public function authToken()
    {
        return $this->auth === 'token' ? 'qux' : parent::authToken();
    }
}
