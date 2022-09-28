<?php

namespace Quarks\EventBus\Dispatcher;

use Quarks\EventBus\Dispatcher\Adapter\DispatcherAdapterInterface;

class Dispatcher
{
    private DispatcherAdapterInterface $adapter;

    public function __construct(DispatcherAdapterInterface $dispatcherAdapter)
    {
        $this->adapter = $dispatcherAdapter;
    }

    public function registerHandler(string $eventName, callable $handler)
    {
        $this->adapter->addListener($eventName, $handler);
    }

    public function dispatch(object $event, string $eventName = null)
    {
        $this->adapter->dispatch($event, $eventName);
    }
}
