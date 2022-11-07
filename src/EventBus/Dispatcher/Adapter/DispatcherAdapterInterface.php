<?php

namespace Quarks\EventBus\Dispatcher\Adapter;

interface DispatcherAdapterInterface
{
    public function dispatch(object $event, string $eventName);
    public function addListener(string $eventName, callable $listener, int $priority = 0);
}
