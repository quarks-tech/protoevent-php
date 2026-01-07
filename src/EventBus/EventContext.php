<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus;

use QuarksTech\ProtoEvent\Event\Metadata;

/**
 * Context passed to event handlers
 */
final class EventContext
{
    public function __construct(
        private Metadata $metadata,
    ) {}

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getEventType(): string
    {
        return $this->metadata->getType();
    }

    public function getEventId(): string
    {
        return $this->metadata->getId();
    }

    public function getSource(): string
    {
        return $this->metadata->getSource();
    }

    public function getSubject(): ?string
    {
        return $this->metadata->getSubject();
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtensions(): array
    {
        return $this->metadata->getExtensions();
    }

    /**
     * @return mixed
     */
    public function getExtension(string $name)
    {
        return $this->metadata->getExtension($name);
    }
}
