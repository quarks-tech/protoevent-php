<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus;

/**
 * Interface for registering event handlers
 */
interface EventHandlerRegistrar
{
    public function registerEventHandler(
        ServiceDesc $serviceDesc,
        string $eventName,
        EventHandler $handler,
    ): void;
}
