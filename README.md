# Api Manager
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/api-manager.svg)](https://packagist.org/packages/laragear/api-manager)
[![Latest stable test run](https://github.com/Laragear/ApiManager/workflows/Tests/badge.svg)](https://github.com/Laragear/ApiManager/actions)
[![Codecov coverage](https://codecov.io/gh/Laragear/ApiManager/branch/1.x/graph/badge.svg?token=V726ud0ss6)](https://codecov.io/gh/Laragear/ApiManager)
[![Maintainability](https://api.codeclimate.com/v1/badges/62cbbc29179121baad6a/maintainability)](https://codeclimate.com/github/Laragear/ApiManager/maintainability)
[![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=Laragear_ApiManager&metric=alert_status)](https://sonarcloud.io/dashboard?id=Laragear_ApiManager)
[![Laravel Octane Compatibility](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://laravel.com/docs/10.x/octane#introduction)

Manage multiple REST servers to make requests in few lines and fluently. No more verbose HTTP Requests!

```php
use App\Http\Apis\Chirper;

$chirp = Chirper::api()->chirp('Hello world!');
```

## Become a sponsor

[![](.github/assets/support.png)](https://github.com/sponsors/DarkGhostHunter)

Your support allows me to keep this package free, up-to-date and maintainable. Alternatively, you can **[spread the word!](http://twitter.com/share?text=I%20am%20using%20this%20cool%20PHP%20package&url=https://github.com%2FLaragear%2FPreload&hashtags=PHP,Laravel)**

## Requirements

* PHP 8.0 or later
* Laravel 9, 10 or later

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
     * The list of simple actions for this API.
     *
     * @var array|string[]
     */
    public array $actions = [
        'latest' => '/',
        'create' => 'post:new',
    ];

    /**
     * Returns the API base URL.
     *
     * @return string
     */
    abstract public function getBaseUrl(): string
    {
        return app()->isProduction()
            ? 'https://chirper.com/api/v1'
            : 'https://dev.chirper.com/api/v1';
    }
     
     /**
      * Returns the Bearer Token used for authentication. 
      * 
      * @return string
      */
     protected function authToken(): string
     {
         return config('services.chirper.secret');
     }
}
```

> **Note**
>
> You can override the API Server stub creating one in `stubs/api.stub`.

### Inline Actions

Actions in the API class solves the problem of having multiple endpoints and preparing each one every time across your application, which can led to errors or convoluted functions full of text.

The easiest way to define actions is to use the `$actions` array using the syntax `verb:route`, being the key the action name. If you don't define a verb, `get` will be inferred.

```php
/**
 * The list of simple actions for this API.
 * 
 * @var array|string[]  
 */
protected $actions = [
    'new chirp' => 'post:new',
    'latest'    => 'latest',
    'view'      => 'chirp/{id}',
    'edit'      => 'update:chirp/{id}',
];
```

While you're at it, add the PHPDoc manually to your API Server to take advantage of autocompletion (intellisense). Just remember that you can access responses as property if these don't require data/parameters, and to take out the first parameter as the path.

```php
use Laragear\ApiManager\ApiServer;

/**
 * @method \Illuminate\Http\Client\Response newChirp($data = [])
 * @property-read \Illuminate\Http\Client\Response $latest
 * @property-read  \Illuminate\Http\Client\Response $view
 * @method \Illuminate\Http\Client\Response edit($data = [])
 */
class Chirper extends ApiServer
{
    // ...
}
```

Then, call the action name in _camelCase_ notation. Arguments will be passed down to the HTTP Request.

```php
use App\Http\Apis\Chirper;

$tweet = Chirper::api()->newChirp(['message' => 'This should be complex']);
```

If the route has named parameters, you can set them as arguments when invoking the server.

```php
use App\Http\Apis\Chirper;

Chirper::api(['id' => 231])->edit(['message' => 'No, it was a breeze!']);

// Same as:
Chirper::api('chirper')->withUrlParameters(['id' => 231])->edit(['message' => 'No, it was a breeze!']);
```

Also, you can call an action without arguments as it where a property.

```php
use App\Http\Apis\Chirper;

$latestChirps = Chirper::api()->latest;
```

### Method actions

For more complex scenarios, you may use a class methods. Just be sure to type-hint the `PendingRequest` as first parameter if you need to customize the request.

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

```php
use App\Http\Apis\Chirper;

$chirp = Chirper::api()->newChirp('Easy peasy');
```

> **Note**
>
> Method actions take precedence over inline actions.

As with inline actions, method actions can be also executed as it where properties if these don't require arguments.

```php
use App\Http\Apis\Chirper;

$latest = Chirper::api()->noReply->newChirp('Easy peasy');
```

### Authentication

An API supports the same three types of authentication as the HTTP Client in Laravel: Basic, Digest and Bearer Token. You may define each of them as an array of username and password using `authBasic()` or `authDigest()`, and `authToken()` with the token, respectively.

```php
/**
 * Returns the Basic Authentication to use against the API.
 * 
 * @var array{string:string}|void
 */
public function authBasic()
{
    return app()->isProduction()
        ? ['app@chirper.com', 'real-password']
        : ['dev@chirper.com', 'fake-password'];
}
```

### Custom Request Build

You have the option to modify the request after it's bootstrapped with `build()` method. You're free to return the same updated request, or a completely new one.

```php
use Illuminate\Http\Client\PendingRequest;

public function build(PendingRequest $request)
{
    $request->connectTimeout(5);
}
```

> **Note**
>
> The `build()` method is executed after the base URL, headers, and authentication, is built.

### Overriding a request

The API request can be overridden as usual. All methods are passed down to the `Illuminate\Http\Client\PendingRequest` instance if these don't exist on the API Class.

```php
use App\Http\Apis\Chirper;

$chirp = Chirper::api()->timeout(5)->latest();
```

> **Note**
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

## Testing

You can easily test if an API Server action works or not by using the `fake()` method of the HTTP facade.

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

If you discover any security related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011-2023 Laravel LLC.
