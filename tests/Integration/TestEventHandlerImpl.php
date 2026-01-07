<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Tests\Integration;

use QuarksTech\ProtoEvent\EventBus\EventContext;

class TestEventHandlerImpl implements TestEventHandler
{
    public bool $eventReceived = false;
    public ?TestEvent $lastEvent = null;
    public ?EventContext $lastContext = null;

    public function handleTestCreatedEvent(EventContext $context, TestEvent $event): void
    {
        echo "\n*** EVENT RECEIVED ***\n";
        echo "Event ID: {$event->getId()}\n";
        echo "Event Name: {$event->getName()}\n";
        echo "CloudEvents ID: {$context->getMetadata()->getId()}\n";
        echo "CloudEvents Type: {$context->getMetadata()->getType()}\n";
        echo "CloudEvents Source: {$context->getMetadata()->getSource()}\n";
        echo "CloudEvents Subject: " . ($context->getMetadata()->getSubject() ?? 'N/A') . "\n";
        echo "******************\n";

        $this->eventReceived = true;
        $this->lastEvent = $event;
        $this->lastContext = $context;

        // Signal to stop consuming
        if (function_exists('posix_kill')) {
            posix_kill(posix_getpid(), SIGTERM);
        }
    }
}
