<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus;

/**
 * Simplified service information for topology setup
 */
final class ServiceInfo
{
    /**
     * @param string $serviceName Service name (exchange name)
     * @param string[] $events Event names (routing keys)
     */
    public function __construct(
        private string $serviceName,
        private array $events,
    ) {}

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * @return string[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }
}
