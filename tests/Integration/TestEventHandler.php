<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Tests\Integration;

use QuarksTech\ProtoEvent\EventBus\EventContext;
use QuarksTech\ProtoEvent\EventBus\EventHandler;

interface TestEventHandler extends EventHandler
{
    public function handleTestCreatedEvent(EventContext $context, TestEvent $event): void;
}
