# Crux

The Anti-Framework.

## Introduction

Crux is a barebones core that can be used to build any HTTP application.  You can use Crux to build your own framework, or your own frameworkless application.

Traditionally micro frameworks combine a dependency injection container, a router, an http stack, and any number of other features.

Crux only provides the HTTP stack.  Unlike micro frameworks, Crux does not provide a router or handle exceptions.  Instead this behavior is added with composable middleware.  By moving these functions out of the core, you can easily reason about the code, remove it, or replace it.

### Understanding Middleware

When a request comes in, it is passed through every middleware that has been registered.  After passing through the middleware it's returned.  A middleware handles a request and return a response.  It's elegantly explained by the function from [StackPHP](http://stackphp.com/):

```
request → λ → response
```

As an example, a microservice API that only creates OAuth2 access tokens might consist of a single middleware.

```php
$app
    ->pipe(function ($request, $response, $next) use ($container) {
        $server = $container->get('League\OAuth2\Server\Server');
        return $server->respondToAccessTokenRequest($request, $response);
    })
    ->run();

```

A more complex Crux application for a JSON API might consist of an entire stack of middleware.

```php
$app
    ->pipe(App\ExceptionMiddleware::class)
    ->pipe(App\CorsMiddleware::class)
    ->pipe(App\TokenAuthorizationMiddleware::class)
    ->pipe(App\JsonContentHandlerMiddleware::class)
    ->pipe(App\RouterMiddleware::class)
    ->run();
```

Middleware can be any valid PHP callable that has the following signature and returns a `Psr\Http\Message\ResponseInterface`:

```php
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

function (Request, $request, Response $response, callable $next)
{
    //
}
```

Middleware is executed in the order it's registered.

## Features

## No Router

The most important distinction is that Crux has no concept of a router.  A router is just a middleware like everything else.

By removing the router from the Application core, developers can use the router that best fits their requirements.  When you discover new requirements that aren't supported by the current router, you can replace a single middleware instead of the entire framework.

## PSR-7 Middleware

Crux middleware is interoperable with many projects such as Slim, Radar, and Expressive.  Just [search packagist for PSR-7 middleware](https://packagist.org/search/?q=psr-7%20middleware) to get an idea of what's out there.

Crux actually implements the middleware interface itself, so you can use a Crux application as middleware!

## Any Container

Crux works with any [container-interop](https://github.com/container-interop/container-interop) container.  If your container isn't supported directly, it will probably work with an [Acclimate adapter](https://github.com/jeremeamia/acclimate-container).

## Usage

### Basics

#### Create An App

To instantiate a new Crux application, pass in a [container-interop](https://github.com/container-interop/container-interop) container.

```php
$container = new League\Container\Container();
$app       = new Yuloh\Crux\Application($container);
```

#### Add Middleware

Now you can add middleware using the `pipe` method.

```php
$app->pipe(function ($request, $response, $next) {
        return $next($request, $response->withStatus(418));
});
```

Any middleware that isn't instantiated will be retrieved from the container.

```php
$app->pipe(App\CorsMiddleware::class);
```

#### Run The Application

To run the application, call the `run` method.

```php
$app->run();
```

The `run` method will emit the response for a PHP SAPI environment.  If you are calling the application from other code (like in a unit test), use the `handle` method.

```php
$response = $app->handle();
```

### Simple Example

The following Crux application would simply return "hello world!".  Go ahead and `composer require league/container`, add this to a file, and run it with `php -S localhost:8000`.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$container = new League\Container\Container();

(new Yuloh\Crux\Application($container))
    ->pipe(function (Request $request, Response $response, $next) {
        $response->getBody()->write('hello world!');
        return $next($request, $response);
    })
    ->run();
```

### Getting The Request And Response Objects

Crux emits an event when a request is received, before it has been passed into the middleware stack.  This allows you to bind the Request and Response objects into the dependency injection container.

```php

use League\Container\Container;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Yuloh\Crux\Event;
use Yuloh\Crux\Application as App;

$container = new Container();
$app       = new App($container);

$app->on(Event::REQUEST_RECEIVED, function (ServerRequest $request, Response $response) use ($container) {
    $container->set('Psr\Http\Message\ServerRequestInterface', $request);
    $container->set('Psr\Http\Message\RequestInterface', $request);
    $container->set('Psr\Http\Message\ResponseInterface', $response);
});
```

### Integrating Crux With Your Application

Crux is meant to be extended.  You can either extend `Yuloh\Crux\Application` or use the `Yuloh\Crux\ApplicationTrait` to use Crux within your app.
