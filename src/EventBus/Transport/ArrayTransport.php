<?php

namespace Quarks\EventBus\Transport;

use Quarks\EventBus\Message;

class ArrayTransport implements TransportInterface
{
    private array $events = [];

    public function publish(string $eventName, $body, array $options = []): void
    {
        $this->events[] = $body;
    }

    public function get(): iterable
    {
        foreach ($this->events as $id => $message) {
            yield new Message($message, ['message_id' => $id]);
        }
    }

    public function ack(Message $message): void
    {
        unset($this->events[$message->getMarker('message_id')]);
    }

    public function reject(Message $message, bool $requeue = false): void
    {
        unset($this->events[$message->getMarker('message_id')]);
    }

    public function setup(array $registeredEvents): void
    {
    }
}
