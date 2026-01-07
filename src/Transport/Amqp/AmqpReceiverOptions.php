<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport\Amqp;

/**
 * Options for AMQP receiver
 */
final class AmqpReceiverOptions
{
    public function __construct(
        private ?string $queueName = null,
        private int $prefetchCount = 3,
        private bool $requeueOnError = false,
        private bool $setupTopology = false,
        private bool $setupBindings = false,
    ) {}

    public function getQueueName(): ?string
    {
        return $this->queueName;
    }

    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }

    public function shouldRequeueOnError(): bool
    {
        return $this->requeueOnError;
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
