<?php

namespace Yuloh\Crux\Tests;

use Yuloh\Container\Container;
use Yuloh\Crux\Application as App;
use Yuloh\Crux\Event;
use Zend\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequestFactory;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testHandle()
    {
        $app = (new App(new Container()))
            ->pipe(function ($req, $res, $next) {
                $res->getBody()->write('hello');
                return $next($req, $res);
            })
            ->pipe(function ($req, $res, $next) {
                $res->getBody()->write(' world!');
                return $next($req, $res);
            });

        $response = $app->handle();
        $this->assertSame('hello world!', $response->getBody()->__toString());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRun()
    {
        $app = (new App(new Container()))
            ->pipe(function ($req, $res, $next) {
                $res->getBody()->write('hello world!');
                return $next($req, $res);
            });

        ob_start();
        $app->run();
        $response = ob_get_clean();
        $this->assertSame('hello world!', $response);
    }

    public function testApplicationCanBeUsedAsMiddleware()
    {
        $middleware = (new App(new Container()))
            ->pipe(function ($req, $res, $next) {
                $res->getBody()->write('hello from middleware!');
                return $next($req, $res);
            });

        $app = (new App(new Container()))
            ->pipe($middleware);

        $response = $app->handle();

        $this->assertSame('hello from middleware!', $response->getBody()->__toString());
    }

    public function testRequestReceievedEventIsEmitted()
    {
        $app = new App(new Container());

        $originalRequest  = ServerRequestFactory::fromGlobals();
        $originalResponse = new Response();
        $app->on(
            Event::REQUEST_RECEIVED,
            function (ServerRequestInterface $request, ResponseInterface $response) use ($originalRequest, $originalResponse) {
                $this->assertSame($originalRequest, $request);
                $this->assertSame($originalResponse, $response);
        });
        $app->handle($originalRequest, $originalResponse);
    }
}
