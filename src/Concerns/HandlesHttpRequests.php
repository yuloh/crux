<?php

namespace Yuloh\Crux\Concerns;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yuloh\Crux\ContainerResolver;
use Yuloh\Crux\Dispatcher;
use Yuloh\Crux\Event;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

trait HandlesHttpRequests
{
    /**
     * Gets the interop container used to resolve middleware.
     *
     * @return \Interop\Container\ContainerInterface
     */
    abstract public function getContainer();

    /**
     * Adds middleware to the stack.
     *
     * @param callable|string $middleware
     *
     * @return $this
     */
    abstract public function pipe(...$middleware);

    /**
     * Emits an event.
     *
     * @param  string $event
     * @param  ...mixed $arguments
     *
     * @return void
     */
    abstract public function emit($event, ...$arguments);

    /**
     * Handles the request and returns a response.
     *
     * This method is an alias for `handle`, and allows the Application
     * to implement the middleware interface.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface|null $request
     * @param  \Psr\Http\Message\ResponseInterface|null      $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request = null, ResponseInterface $response = null)
    {
        return $this->handle($request, $response);
    }

    /**
     * Handles the request and returns a response.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface|null $request
     * @param  \Psr\Http\Message\ResponseInterface|null      $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(ServerRequestInterface $request = null, ResponseInterface $response = null)
    {
        $request  = $request ?: ServerRequestFactory::fromGlobals();
        $response = $response ?: new Response();

        $this->emit(Event::REQUEST_RECEIVED, $request, $response);

        return $this->dispatchThroughMiddleware($request, $response);
    }

    /**
     * Processes the request and emits a response for a PHP SAPI environment.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface|null $request
     * @param  \Psr\Http\Message\ResponseInterface|null      $response
     */
    public function run(ServerRequestInterface $request = null, ResponseInterface $response = null)
    {
        $response = $this->handle($request, $response);

        (new Response\SapiEmitter())->emit($response);
    }

    /**
     * Dispatches the request and response through the registered middleware.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request
     * @param  \Psr\Http\Message\ResponseInterface      $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function dispatchThroughMiddleware(ServerRequestInterface $request, ResponseInterface $response)
    {
        $dispatcher = new Dispatcher($this->middleware, new ContainerResolver($this->getContainer()));

        return $dispatcher->dispatch($request, $response);
    }
}
