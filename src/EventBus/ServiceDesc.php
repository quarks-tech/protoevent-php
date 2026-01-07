<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus;

/**
 * Descriptor for a service (collection of events)
 */
final class ServiceDesc
{
    /**
     * @param string $serviceName Full service name (e.g., "example.books.v1")
     * @param EventDesc[] $events List of event descriptors
     * @param string $metadata Source proto file name
     */
    public function __construct(
        private string $serviceName,
        private array $events,
        private string $metadata,
    ) {}

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * @return EventDesc[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function getMetadata(): string
    {
        return $this->metadata;
    }

    public function getEventDesc(string $name): ?EventDesc
    {
        foreach ($this->events as $event) {
            if ($event->getName() === $name) {
                return $event;
            }
        }
        return null;
    }

    /**
     * @return string[]
     */
    public function getEventNames(): array
    {
        return array_map(fn(EventDesc $e) => $e->getName(), $this->events);
    }
}
