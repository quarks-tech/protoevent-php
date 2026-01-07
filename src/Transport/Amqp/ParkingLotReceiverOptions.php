<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport\Amqp;

/**
 * Options for Parking Lot Receiver
 */
final class ParkingLotReceiverOptions
{
    public function __construct(
        private string $queueName,
        private int $prefetchCount = 3,
        private int $maxRetries = 3,
        private int $retryBackoffMs = 15000,
        private bool $setupTopology = false,
        private bool $setupBindings = false,
    ) {}

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function getRetryBackoffMs(): int
    {
        return $this->retryBackoffMs;
    }

    public function shouldSetupTopology(): bool
    {
        return $this->setupTopology;
    }

    public function shouldSetupBindings(): bool
    {
        return $this->setupBindings;
    }
}
