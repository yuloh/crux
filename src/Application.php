<?php

namespace Yuloh\Crux;

use Interop\Container\ContainerInterface;
use Yuloh\Container\Container;

class Application
{
    use ApplicationTrait;

    /**
     * @var callable|string[]
     */
    private $middleware = [];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Application constructor.
     *
     * @param \Interop\Container\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Gets the interop container used to resolve middleware.
     *
     * @return \Interop\Container\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Adds middleware to the stack.
     *
     * @param callable|string $middleware
     *
     * @return $this
     */
    public function pipe(...$middleware)
    {
        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }
}
