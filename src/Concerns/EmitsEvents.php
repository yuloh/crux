<?php

namespace Yuloh\Crux\Concerns;

trait EmitsEvents
{
    /**
     * @var []
     */
    private $listeners = [];

    /**
     * Registers a new listener.
     *
     * @param  string   $event
     * @param  callable $listener
     * @return void
     */
    public function on($event, callable $listener)
    {
        if (!array_key_exists($event, $this->listeners)) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $listener;
    }

    /**
     * Emits an event.
     *
     * @param  string   $event
     * @param  ...mixed $arguments
     * @return void
     */
    public function emit($event, ...$arguments)
    {
        foreach ($this->listeners($event) as $listener) {
            $listener(...$arguments);
        }
    }

    /**
     * Returns the listeners for the given event.
     *
     * @param  string $event
     * @return []
     */
    private function listeners($event)
    {
        return array_key_exists($event, $this->listeners) ? $this->listeners[$event] : [];
    }
}
