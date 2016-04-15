<?php

namespace Yuloh\Crux\Tests;

use Yuloh\Crux\Dispatcher;
use Yuloh\Crux\ContainerResolver;
use Yuloh\Container\Container;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testDispatch()
    {
        $dispatcher = new Dispatcher(
            [
                function ($req, $res, $next) {
                    $res->getBody()->write('hello');
                    return $next($req, $res);
                },
                function ($req, $res, $next) {
                    $res->getBody()->write(' world');
                    return $next($req, $res);
                }
            ]
        );

        $response = $dispatcher->dispatch(ServerRequestFactory::fromGlobals(), new Response());
        $actual = (string) $response->getBody();
        $this->assertSame('hello world', $actual);

        $response = $dispatcher->dispatch(ServerRequestFactory::fromGlobals(), new Response());
        $actual = (string) $response->getBody();
        $this->assertSame('hello world', $actual, 'You should be able to dispatch multiple times');
    }

    public function testDispatchWithResolver()
    {
        $container = new Container();
        $container->set('first', function () {
            return function ($req, $res, $next) {
                $res->getBody()->write('1');
                return $next($req, $res);
            };
        });
        $container->set('second', function () {
            return function ($req, $res, $next) {
                $res->getBody()->write('2');
                return $next($req, $res);
            };
        });

        $middleware = [
            'first',
            'second',
            function ($req, $res, $next) {
                return $next($req, $res);
            }
        ];
        $resolver = new ContainerResolver($container);
        $dispatcher = new Dispatcher($middleware, $resolver);
        $response = $dispatcher->dispatch(ServerRequestFactory::fromGlobals(), new Response());
        $actual = (string) $response->getBody();
        $this->assertSame('12', $actual);
    }
}
