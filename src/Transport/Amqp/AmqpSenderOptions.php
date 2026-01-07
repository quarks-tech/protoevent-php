<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport\Amqp;

/**
 * Options for AMQP sender
 */
final class AmqpSenderOptions
{
    public const DELIVERY_MODE_TRANSIENT = 1;
    public const DELIVERY_MODE_PERSISTENT = 2;

    public function __construct(
        private int $deliveryMode = self::DELIVERY_MODE_PERSISTENT,
        private bool $setupTopology = false,
    ) {}

    public function getDeliveryMode(): int
    {
        return $this->deliveryMode;
    }

    public function shouldSetupTopology(): bool
    {
        return $this->setupTopology;
    }
}
