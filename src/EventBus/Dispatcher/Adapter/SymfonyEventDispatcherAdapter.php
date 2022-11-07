<?php

namespace Quarks\EventBus\Dispatcher\Adapter;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SymfonyEventDispatcherAdapter implements DispatcherAdapterInterface
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function dispatch(object $event, string $eventName)
    {
        $this->dispatcher->dispatch($event, $eventName);
    }

    public function addListener(string $eventName, callable $listener, int $priority = 0)
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);
    }
}
