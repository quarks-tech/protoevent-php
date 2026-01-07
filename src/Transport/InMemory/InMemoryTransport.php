<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport\InMemory;

use QuarksTech\ProtoEvent\Event\Metadata;
use QuarksTech\ProtoEvent\EventBus\ServiceDesc;
use QuarksTech\ProtoEvent\Transport\ReceiverInterface;
use QuarksTech\ProtoEvent\Transport\SenderInterface;

/**
 * In-memory transport for testing purposes
 *
 * Stores sent messages in memory and allows receiving them.
 */
final class InMemoryTransport implements SenderInterface, ReceiverInterface
{
    /** @var array<int, array{metadata: Metadata, data: string}> */
    private array $messages = [];

    /** @var array<string, ServiceDesc> */
    private array $services = [];

    private bool $running = false;

    public function send(Metadata $metadata, string $data): void
    {
        $this->messages[] = [
            'metadata' => $metadata,
            'data' => $data,
        ];
    }

    public function setup(ServiceDesc $serviceDesc): void
    {
        $this->services[$serviceDesc->getServiceName()] = $serviceDesc;
    }

    public function receive(callable $processor): void
    {
        $this->running = true;

        // Note: $this->running can be set to false by stop() called from within $processor
        /** @psalm-suppress RedundantCondition */
        while ($this->running && count($this->messages) > 0) {
            $message = array_shift($this->messages);
            $processor($message['metadata'], $message['data']);
        }

        $this->running = false;
    }

    public function stop(): void
    {
        $this->running = false;
    }

    /**
     * Get all messages currently in the transport
     *
     * @return array<int, array{metadata: Metadata, data: string}>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Get message count
     */
    public function count(): int
    {
        return count($this->messages);
    }

    /**
     * Check if a service was set up
     */
    public function hasService(string $serviceName): bool
    {
        return isset($this->services[$serviceName]);
    }

    /**
     * Get all set up services
     *
     * @return array<string, ServiceDesc>
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * Clear all messages
     */
    public function clear(): void
    {
        $this->messages = [];
    }

    /**
     * Reset the transport (clear messages and services)
     */
    public function reset(): void
    {
        $this->messages = [];
        $this->services = [];
        $this->running = false;
    }
}
