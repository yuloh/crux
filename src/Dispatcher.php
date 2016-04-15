<?php

namespace Yuloh\Crux;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Dispatcher
{
    /**
     * The middleware stack.
     *
     * @var callable[]
     */
    private $stack = [];

    /**
     * The resolver used to resolve middleware.
     *
     * @var callable
     */
    private $resolver;

    /**
     * Dispatcher constructor.
     *
     * @param callable[] $stack
     * @param callable   $resolver
     */
    public function __construct(array $stack, callable $resolver = null)
    {
        $this->stack    = $stack;
        $this->resolver = $resolver;
    }

    /**
     * Dispatches the request and response through the middleware stack.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->__invoke($request, $response);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $stack      = $this->stack;
        $middleware = array_shift($stack);
        $middleware = $this->resolve($middleware);

        return $middleware($request, $response, $this->withStack($stack));
    }

    /**
     * Returns a new instance with the given middleware stack.
     *
     * @param callable[] $stack
     *
     * @return \Yuloh\Crux\Dispatcher
     */
    public function withStack(array $stack)
    {
        return new self($stack, $this->resolver);
    }

    /**
     * Resolves the given middleware.
     *
     * @param mixed $middleware
     *
     * @return callable
     */
    private function resolve($middleware)
    {
        if (!$middleware) {
            return $this->lastStage();
        }

        return $this->resolver ? call_user_func($this->resolver, $middleware) : $middleware;
    }

    /**
     * Returns a callable for usage as the final stage of the middleware stack.
     *
     * @return \Closure
     */
    private function lastStage()
    {
        return function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
            return $response;
        };
    }
}
