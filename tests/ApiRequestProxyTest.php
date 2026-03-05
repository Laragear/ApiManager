<?php

namespace Tests;

use BadMethodCallException;
use ErrorException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laragear\ApiManager\ApiServer;
use Laragear\ApiManager\Attributes\Action;
use LogicException;
use PHPUnit\Framework\Attributes\Test;

use function func_get_args;

class ApiRequestProxyTest extends TestCase
{
    #[Test]
    public function throws_when_api_has_empty_base_url(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('There is no base URL for this [TestEmptyApiUrlServer] API.');

        TestEmptyApiUrlServer::api();
    }

    #[Test]
    public function use_api_properties_to_build_request(): void
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

    #[Test]
    public function builds_default_request(): void
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

    #[Test]
    public function builds_on_inline_action_get(): void
    {
        Http::fake();

        TestActionApiServer::api()->foo();

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('GET', $request->method());
            static::assertSame('https://www.test.com/foo/action', $request->url());

            return true;
        });
    }

    #[Test]
    public function builds_on_inline_action_with_get_verb(): void
    {
        Http::fake();

        TestActionApiServer::api()->bar();

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('GET', $request->method());
            static::assertSame('https://www.test.com/bar/action', $request->url());

            return true;
        });
    }

    #[Test]
    public function builds_on_inline_action_using_camel_case(): void
    {
        Http::fake();

        TestActionApiServer::api()->bazQuz();

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('https://www.test.com/baz/quz', $request->url());

            return true;
        });
    }

    #[Test]
    public function builds_on_inline_action_using_http_verb(): void
    {
        Http::fake();

        TestActionApiServer::api()->bazQuz();

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('POST', $request->method());
            static::assertSame('https://www.test.com/baz/quz', $request->url());

            return true;
        });
    }

    #[Test]
    public function builds_on_inline_action_with_url_parameters(): void
    {
        Http::fake();

        TestActionApiServer::api(['id' => 10])->parameter();

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('POST', $request->method());
            static::assertSame('https://www.test.com/baz/quz/10', $request->url());

            return true;
        });
    }

    #[Test]
    public function builds_on_inline_action_with_hacky_verb_and_path(): void
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

    #[Test]
    public function throws_when_method_doesnt_exist_in_pending_request(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method Illuminate\Http\Client\PendingRequest::invalid does not exist.');

        TestActionApiServer::api()->invalid();
    }

    #[Test]
    public function builds_on_action_method_with_parameters(): void
    {
        Http::fake();

        $request = TestActionApiServer::api()->override('test');

        static::assertSame('test', $request);

        Http::assertNothingSent();
    }

    #[Test]
    public function builds_on_action_method_with_pending_request_type_hinting(): void
    {
        Http::fake();

        $response = TestActionApiServer::api()->requestFirst('foo', 'bar', 'baz');
        static::assertInstanceOf(PendingRequest::class, $response[0]);
        static::assertSame('foo', $response[1]);
        static::assertSame('bar', $response[2]);
        static::assertSame('baz', $response[3]);

        $response = TestActionApiServer::api()->requestMiddle('foo', 'bar', 'baz');
        static::assertSame('foo', $response[0]);
        static::assertSame('bar', $response[1]);
        static::assertInstanceOf(PendingRequest::class, $response[2]);
        static::assertSame('baz', $response[3]);

        $response = TestActionApiServer::api()->requestLast('foo', 'bar', 'baz');
        static::assertSame('foo', $response[0]);
        static::assertSame('bar', $response[1]);
        static::assertSame('baz', $response[2]);
        static::assertInstanceOf(PendingRequest::class, $response[3]);

        $response = TestActionApiServer::api()->requestOptional('foo', 'bar');
        static::assertSame('foo', $response[0]);
        static::assertInstanceOf(PendingRequest::class, $response[1]);
        static::assertSame('bar', $response[2]);

        Http::assertNothingSent();
    }

    #[Test]
    public function forwards_calls_to_the_request(): void
    {
        Http::fake();

        TestActionApiServer::api()->setBaseUrl('www.google.com')->get('test');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('GET', $request->method());
            static::assertSame('www.google.com/test', $request->url());

            return true;
        });
    }

    #[Test]
    public function forwards_properties_to_api_server_action(): void
    {
        Http::fake();

        $result = TestActionApiServer::api()->asProperty;

        static::assertSame('as property', $result);
    }

    #[Test]
    public function throws_when_property_does_not_exist_in_api(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined property: Tests\TestActionApiServer::$badProperty');

        TestActionApiServer::api()->badProperty;
    }

    #[Test]
    public function forwards_properties_to_api_server_inline_action(): void
    {
        Http::fake();

        TestActionApiServer::api()->foo;

        Http::assertSent(static function (Request $request): bool {
            static::assertSame('GET', $request->method());
            static::assertSame('https://www.test.com/foo/action', $request->url());

            return true;
        });
    }

    #[Test]
    public function uses_auth_basic(): void
    {
        Http::fake();

        TestAuthApiServer::api()->useAuth('basic', ['user', 'pass'])->get('test');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame(['Basic dXNlcjpwYXNz'], $request->header('Authorization'));

            return true;
        });

        TestAuthApiServer::api()->useAuth('basic', ['username' => 'user', 'password' => 'pass'])->get('test');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame(['Basic dXNlcjpwYXNz'], $request->header('Authorization'));

            return true;
        });

        TestAuthApiServer::api()->useAuth('basic', ['user' => 'pass'])->get('test');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame(['Basic dXNlcjpwYXNz'], $request->header('Authorization'));

            return true;
        });
    }

    #[Test]
    public function uses_auth_digest(): void
    {
        Http::fake();

        TestAuthApiServer::api()
            ->useAuth('digest', ['user', 'pass'])
            ->beforeSending(static function (Request $request, array $options): void {
                static::assertSame(['user', 'pass', 'digest'], $options['auth']);
            })
            ->get('test');

        Http::fake();

        TestAuthApiServer::api()
            ->useAuth('digest', ['username' => 'user', 'password' => 'pass'])
            ->beforeSending(static function (Request $request, array $options): void {
                static::assertSame(['user', 'pass', 'digest'], $options['auth']);
            })
            ->get('test');
    }

    #[Test]
    public function uses_auth_token(): void
    {
        Http::fake();

        TestAuthApiServer::api()->useAuth('token', ['test_token'])->get('test');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame(['Bearer test_token'], $request->header('Authorization'));

            return true;
        });

        Http::fake();

        TestAuthApiServer::api()->useAuth('token', ['test_token', 'Custom'])->get('test');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame(['Custom test_token'], $request->header('Authorization'));

            return true;
        });

        Http::fake();

        TestAuthApiServer::api()->useAuth('token', ['token' => 'test_token', 'type' => 'Custom'])->get('test');

        Http::assertSent(static function (Request $request): bool {
            static::assertSame(['Custom test_token'], $request->header('Authorization'));

            return true;
        });
    }

    #[Test]
    public function support_pools(): void
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

    #[Test]
    public function support_pools_with_named_requests(): void
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

    #[Test]
    public function wraps_into_custom_response_if_set_as_action(): void
    {
        Http::fake([
            'https://www.test.com/200' => Http::response('', 200),
        ]);

        $result = TestAuthWrapRequestServerAction::api()->example();

        static::assertInstanceOf(Fixtures\CustomResponse::class, $result);
    }

    #[Test]
    public function wraps_into_custom_response_if_set_as_method(): void
    {
        Http::fake([
            'https://www.test.com/200' => Http::response('', 200),
        ]);

        $result = TestAuthWrapRequestServerMethod::api()->example();

        static::assertInstanceOf(Fixtures\CustomResponse::class, $result);
    }

    #[Test]
    public function wraps_int_custom_response_if_set_async_action(): void
    {
        Http::fake([
            'https://www.test.com/200' => Http::response('', 200),
        ]);

        $result = TestAuthWrapRequestServerAction::api()->async()->example()->wait();

        static::assertInstanceOf(Fixtures\CustomResponse::class, $result);
    }

    #[Test]
    public function wraps_int_custom_response_if_set_async_method(): void
    {
        Http::fake([
            'https://www.test.com/200' => Http::response('', 200),
        ]);

        $result = TestAuthWrapRequestServerMethod::api()->async()->example()->wait();

        static::assertInstanceOf(Fixtures\CustomResponse::class, $result);
    }
}

class TestPropertiesApiServer extends ApiServer
{
    public function getBaseUrl(): string
    {
        return  'https://www.properties.com';
    }

    public array$headers = ['X-Foo' => 'bar'];

    public ?int $timeout = 10;
}

#[Action('foo', 'foo/action')]
#[Action('bar', 'get', 'bar/action')]
#[Action('bazQuz', 'post', 'baz/quz')]
#[Action('parameter', 'post', 'baz/quz/{id}')]
#[Action('invalid', 'invalid', '/something')]
#[Action('hacky', 'baseUrl', 'www.google.com')]
#[Action('override', 'get', '/not-overridden')]
#[Action('asProperty', 'get', '/not-overridden-property')]
class TestActionApiServer extends ApiServer
{
    public string $url;

    public function setBaseUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    public function getBaseUrl(): string
    {
        return $this->url ?? 'https://www.test.com';
    }

    public function override(PendingRequest $request, string $message)
    {
        return $message;
    }

    public function requestFirst(PendingRequest $first, string $second, string $third, string $fourth)
    {
        return func_get_args();
    }

    public function requestMiddle(string $first, string $second, PendingRequest $third, string $fourth)
    {
        return func_get_args();
    }

    public function requestLast(string $first, string $second, string $third, PendingRequest $fourth)
    {
        return func_get_args();
    }

    public function requestOptional(string $first, PendingRequest $second, ?string $third = null, ?string $fourth = null)
    {
        return func_get_args();
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
    public array $auth = ['', []];

    public function useAuth(string $auth, array $data): static
    {
        $this->auth = [$auth, $data];

        return $this;
    }

    public function authBasic()
    {
        return $this->auth[0] === 'basic' ? $this->auth[1] : parent::authBasic();
    }

    public function authDigest()
    {
        return $this->auth[0] === 'digest' ? $this->auth[1] : parent::authDigest();
    }

    public function authToken()
    {
        return $this->auth[0] === 'token' ? $this->auth[1] : parent::authToken();
    }
}

class TestAuthWrapRequestServerMethod extends ApiServer
{
    public array $responses = [
        'example' => Fixtures\CustomResponse::class,
    ];

    public function getBaseUrl(): string
    {
        return 'https://www.test.com';
    }

    public function example(PendingRequest $request)
    {
        return $request->get('/200');
    }
}

/**
 * @method \Illuminate\Http\Client\Response example()
 */
#[Action('example', '200', response: Fixtures\CustomResponse::class)]
class TestAuthWrapRequestServerAction extends ApiServer
{
    public function getBaseUrl(): string
    {
        return 'https://www.test.com';
    }
}
