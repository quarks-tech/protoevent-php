<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus;

use Google\Protobuf\Internal\Message;

/**
 * Interface for publishing events
 */
interface EventPublisher
{
    /**
     * Publish an event
     *
     * @param string $eventType Full event type (e.g., "example.books.v1.BookCreated")
     * @param Message $event The protobuf message to publish
     * @param PublishOption ...$options Publishing options
     */
    public function publish(string $eventType, Message $event, PublishOption ...$options): void;
}
