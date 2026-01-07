<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus;

/**
 * @internal
 */
final class EventHandlerInfo
{
    public function __construct(
        private EventHandler $handler,
        private EventDesc $eventDesc,
    ) {}

    public function getHandler(): EventHandler
    {
        return $this->handler;
    }

    public function getEventDesc(): EventDesc
    {
        return $this->eventDesc;
    }
}
