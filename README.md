# Api Manager
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/api-manager.svg)](https://packagist.org/packages/laragear/api-manager)
[![Latest stable test run](https://github.com/Laragear/ApiManager/workflows/Tests/badge.svg)](https://github.com/Laragear/ApiManager/actions)
[![Codecov Coverage](https://codecov.io/gh/Laragear/ApiManager/graph/badge.svg?token=V726ud0ss6)](https://codecov.io/gh/Laragear/ApiManager)
[![Maintainability](https://qlty.sh/badges/f2df384c-4439-4715-948f-97d94966cfb7/maintainability.svg)](https://qlty.sh/gh/Laragear/projects/ApiManager)
[![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=Laragear_ApiManager&metric=alert_status)](https://sonarcloud.io/dashboard?id=Laragear_ApiManager)
[![Laravel Octane Compatibility](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://laravel.com/docs/11.x/octane#introduction)

Manage multiple REST servers to make requests in few lines and fluently. No more verbose HTTP Requests!

```php
use App\Http\Apis\Chirper;

$chirp = Chirper::api()->chirp('Hello world!');
```

## Become a sponsor

[![](.github/assets/support.png)](https://github.com/sponsors/DarkGhostHunter)

Your support allows me to keep this package free, up-to-date and maintainable. Alternatively, you can **[spread the word!](http://twitter.com/share?text=I%20am%20using%20this%20cool%20PHP%20package&url=https://github.com%2FLaragear%2FPreload&hashtags=PHP,Laravel)**

## Requirements

* PHP 8.3 or later
* Laravel 12 or later

## Installation

Require this using Composer into your project:

```bash
composer require laragear/api-manager
```

## Usage

### Creating an API Server

To make use of an API server, define a class that extends `Laragear\ApiManager\ApiServer`. You may use the `make:api` Artisan command to make a ready-made stub in the `app\Http\Apis` directory.

```shell
php artisan make:api Chirper
```

You will receive a file with a base URL and actions, and space to add some headers and a bearer token. You're free to adjust it to your needs.

```php
namespace App\Http\Apis;

use Laragear\ApiManager\ApiServer;
use Laragear\ApiManager\Attributes\Action;

#[Action('latest', '/')]
#[Action('create', 'post', 'new')]
class Chirper extends ApiServer
{
    /**
     * The headers to include in each request.
     *
     * @var array{string:string}|array
     */
    public array $headers = [
        // ...
    ];
    
    /**
     * Returns the API base URL.
     */
    public function getBaseUrl(): string
    {
        return app()->isProduction()
            ? 'https://chirper.com/api/v1'
            : 'https://dev.chirper.com/api/v1';
    }
     
     /**
      * Returns the Bearer Token used for authentication. 
      */
     protected function authToken(): string
     {
         return config('services.chirper.secret');
     }
}
```

> [!NOTE]
>
> You can override the API Server stub creating one in `stubs/api.stub`.

### Inline Actions

Setting actions in the API class solves the problem of having multiple endpoints and preparing each one every time across your application, which can led to errors or convoluted functions full of text.

The easiest way to define actions is to use the `Laragear\ApiManager\Attributes\Action` attribute on top of the class with the action name in camelCase (like it was a class method), and the URL path. You may also set the HTTP verb to use, otherwise `get` will be inferred. 

```php
namespace App\Http\Apis;

use Laragear\ApiManager\ApiServer;
use Laragear\ApiManager\Attributes\Action;

#[Action('newChirp', 'post', 'new')]
#[Action('latest', 'latest')]
#[Action('view', 'chirp/{id}')]
#[Action('edit', 'update', 'chirp/{id}')]
#[Action('delete', 'delete', 'chirp/{id}')]
class Chirper extends ApiServer
{
    // ...
}
```

For example, to see the latest chirp on the server, we could call `latest` directly from our `ChirpApi`.

```php
use App\Http\Apis\Chirper;

$latestChirps = Chirper::api()->latest();
```

Alternatively, you can call an action without arguments as it were a property, especially if just a `GET` method over the server.

```php
use App\Http\Apis\Chirper;

$latestChirps = Chirper::api()->latest;
```

When calling an action that requires parameters, the arguments will be passed down to the HTTP Request as part of the body.

```php
use App\Http\Apis\Chirper;

// Create a new chirp.
$chirp = Chirper::api()->newChirp(['message' => 'This should be complex']);
```

If the route has named parameters, lile `chirp/{id}`, you can set them as arguments when invoking the server.

```php
use App\Http\Apis\Chirper;

// Edit a chirp.
Chirper::api(['id' => 231])->edit(['message' => 'No, it was a breeze!']);

// Same as:
Chirper::api('chirper')->withUrlParameters(['id' => 231])->edit(['message' => 'No, it was a breeze!']);
```

While you're at it, add the PHPDoc manually to your API Server to take advantage of autocompletion in your IDE (intellisense).

```php
use Laragear\ApiManager\ApiServer;
use Laragear\ApiManager\Attributes\Action;

/**
 * @method \Illuminate\Http\Client\Response newChirp($data = [])
 * @property-read \Illuminate\Http\Client\Response $latest
 * @property-read  \Illuminate\Http\Client\Response $view
 * @method \Illuminate\Http\Client\Response edit($data = [])
 * @method \Illuminate\Http\Client\Response delete()
 */
 
#[Action('newChirp', 'post', 'new')]
#[Action('latest', 'latest')]
#[Action('view', 'chirp/{id}')]
#[Action('edit', 'update', 'chirp/{id}')]
#[Action('delete', 'delete', 'chirp/{id}')]
class Chirper extends ApiServer
{
    // ...
}
```

> [!NOTE]
> 
> You can still use the old way of using the `$actions` array, but is being deprecated.

### Method actions

For more complex scenarios, you may use a class method. Just be sure to type-hint the `PendingRequest` on any parameter if you need to customize the request.

```php
use Illuminate\Http\Client\PendingRequest;

public function newChirp(PendingRequest $request, string $message)
{
    return $request->connectTimeout(10)->post('new', ['message' => $message]);
}

public function noReply(PendingRequest $request)
{
    $request->withHeaders(['X-No-Reply' => 'false'])
    
    return $this;
}
```

Then later, you can invoke the class methods like any Monday morning.

```php
use App\Http\Apis\Chirper;

$chirp = Chirper::api()->newChirp('Easy peasy');
```

> [!NOTE]
>
> Method actions take precedence over inline actions.

As with inline actions, method actions can be also executed as it where properties if these don't require arguments.

```php
use App\Http\Apis\Chirper;

$latest = Chirper::api()->noReply->newChirp('Easy peasy');
```

### Authentication

An API Server supports the [three kinds authentication of the HTTP Client in Laravel](https://laravel.com/docs/11.x/http-client#authentication): Basic, Digest and Bearer Token. You may define each of them as an array of username and password using `authBasic()` or `authDigest()`, and `authToken()` with the token, respectively.

```php
/**
 * Returns the Basic Authentication to use against the API.
 * 
 * @var array{string, string}|array<string>|void
 */
public function authBasic(): array
{
    return app()->isProduction()
        ? ['app@chirper.com' => 'real-password']
        : ['dev@chirper.com' => 'fake-password'];
}
```

> [!WARNING]
> 
> Don't use an associative array to match the underlying methods. Since [Laravel doesn't warranty consistency on named arguments](https://laravel.com/docs/11.x/releases#named-arguments), you should opt for simple arrays or `key => value`.
> 
> ```php
> // This is still supported, but discouraged!
> return ['username' => 'app@chirper', 'password' => 'real-password'];
> ```

### Before & After building a request

You can modify the request before and after it's bootstrapped using the `beforeBuild()` and `afterBuild()` respectively. The `beforeBuild()` is executed after the `PendingRequest` instance receives the base URL, and the `afterBuild()` is called after the headers and authentication are incorporated.

```php
use Illuminate\Http\Client\PendingRequest;

public function beforeBuild(PendingRequest $request)
{
    //
}

public function afterBuild(PendingRequest $request)
{
    //
}
```

You're free here to tap into the request instance and modify it for all endpoints, or return an entirely new `PendingRequest` instance.

### Overriding a request

The API request can be overridden as usual. All methods are passed down to the `Illuminate\Http\Client\PendingRequest` instance if these don't exist on the API Class.

```php
use App\Http\Apis\Chirper;

$chirp = Chirper::api()->timeout(5)->latest();
```

> [!NOTE]
>
> If the method exists in your API Class, it will take precedence.

### Dependency Injection

All API Servers are resolved using the Service Container, so you can add any service you need to inject in your object through the constructor.

```php
use Illuminate\Filesystem\Filesystem;
use Laragear\ApiManager\ApiServer;

class Chirper extends ApiServer
{
    public function __construct(protected Filesystem $file)
    {
        if ($this->file->missing('important_file.txt')) {
            throw new RuntimeException('Important file missing!')
        }
    }
    
    // ...
}
```

You can also create a callback to resolve your API Server in your `AppServiceProvider` if you need more deep customization to create it.

```php
// app\Providers\AppServiceProvider.php
use App\Http\Apis\Chirper;

public function register()
{
    $this->app->bind(Chirper::class, function () {
       return new Chirper(config('services.chirper.version'));
    })
}
```

### Concurrent Requests

To add an API Server Request to a pool, use the `onPool()` method for each concurrent request. There is no need to make all requests to the same API server, as you can mix and match different destinations.

```php
use Illuminate\Support\Facades\Http;
use App\Http\Apis\Chirper;
use App\Http\Apis\Twitter;

$responses = Http::pool(fn ($pool) => [
    Chirper::api()->on($pool)->chirp('Hello world!'),
    Twitter::api()->on($pool)->tweet('Goodbye world!'),
    $pool->post('mastodon.org/api', ['message' => 'Greetings citizens!'])
]);
 
return $responses[0]->ok();
```

You may also name the requests using a second argument to `on()`.

```php
use Illuminate\Support\Facades\Http;
use App\Http\Apis\Chirper;
use App\Http\Apis\Twitter;

$responses = Http::pool(fn ($pool) => [
    Chirper::api()->on($pool, 'first')->chirp('Hello world!'),
    Twitter::api()->on($pool, 'second')->tweet('Goodbye world!'),
    $pool->as('third')->post('mastodon.org/api', ['message' => 'Greetings citizens!'])
]);
 
return $responses['first']->ok();
```

### Wrapping into custom responses

You may find yourself receiving a response and having to map the data to your own class manually for convenience. Instead of juggling your way to do that, you can automatically wrap the incoming response into a custom "API Response." 

First, create a custom response for an api using `make:api-response`, the API you want to use, and name the custom response with the same name of the endpoint. Ideally, you would want to name it the same as the action or method you plan to use it for. 

```shell
php artisan make:api-response Chirper ViewResponse
```

You will receive a file in `App\Http\Apis\{ApiServer}\Responses` like this:

```php
namespace App\Http\Apis\Chirper\Responses;

use Illuminate\Http\Client\Response;

class ViewResponse extends Response
{
    //
}
```

> [!NOTE]
>
> You can override the API Response stub creating one in `stubs/api-response.stub`.

In this class you can make any method you want. Since your class will extend the base [Laravel HTTP Client](https://laravel.com/docs/11.x/http-client) `Response` class, you will have access to all its convenient methods. 

```php
class ViewResponse extends Response
{
    /**
     * Determine if the chirp is private.
     */
    public function isPrivate(): bool
    {
        return $this->json('metadata.is_private', false)
    }
}
```

Once you finish up customizing your custom API Response, you may set the `Laragear\ApiManager\Attributes\Response` attribute with the response class name on top of your method.

```php
use Laragear\ApiManager\Attributes\Response;

#[Response(Responses\ViewResponse::class)]
public function view()
{
    //...
}
```

> [!NOTE]
> 
> You can still use the old `$responses` array to map custom Responses to your method, but is being deprecated.

This will enable the API Manager to wrap the response into your own every time you call that method to receive a response. For example, if you call `view()`, you will receive a new `ViewResponse` instance.

```php
use App\Http\Apis\Chirper;

$chirp = Chirper::api(['id' => 5])->view();

if ($chirp->successful() && $chirp->isPrivate()) {
    return 'The chirp cannot be seen publicly.';
}
```

> [!TIP]
> 
> When using `async()` requests, custom responses are automatically wrapped once resolved. 

## Testing

You can test if an API Server action works or not by using [the `fake()` method of the HTTP facade](https://laravel.com/docs/11.x/http-client#testing).

```php
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request;

public function test_creates_new_chirp(): void
{
    Http::fake(function (Request $request) {
        return Http::response([
            'posted' => 'ok', 
            ...json_decode($request->body())
        ], 200);
    });
    
    $this->post('spread-message', ['message' => 'Hello world!'])
        ->assertSee('Posted!');
}
```

## Laravel Octane Compatibility

* There are no singletons using a stale application instance.
* There are no singletons using a stale config instance.
* There are no singletons using a stale request instance.
* There are no static properties written.

There should be no problems using this package with Laravel Octane.

## Security

If you discover any security-related issues, please [use the online form](https://github.com/Laragear/ApiManager/security).

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at the time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011–2026 Laravel LLC.
