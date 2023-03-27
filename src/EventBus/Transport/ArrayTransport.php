<?php

namespace Quarks\EventBus\Transport;

use Quarks\EventBus\Envelope;

class ArrayTransport implements TransportInterface
{
    private array $events = [];

    public function publish(Envelope $envelope, array $options = []): void
    {
        $this->events[$envelope->getMetadata()->getId()] = $envelope;
    }

    public function get(): iterable
    {
        foreach ($this->events as $event) {
            yield $event;
        }
    }

    public function ack(Envelope $envelope): void
    {
        unset($this->events[$envelope->getMetadata()->getId()]);
    }

    public function reject(Envelope $envelope, bool $requeue = false): void
    {
        unset($this->events[$envelope->getMetadata()->getId()]);
    }

    public function setup(array $registeredEvents): void
    {
    }
}
