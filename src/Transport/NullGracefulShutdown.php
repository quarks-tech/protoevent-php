<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport;

/**
 * No-op graceful shutdown implementation
 *
 * Use this for testing or when graceful shutdown is not needed.
 * Note: trigger() still works to allow programmatic stopping.
 */
final class NullGracefulShutdown implements GracefulShutdownInterface
{
    private bool $triggered = false;

    public function isTriggered(): bool
    {
        return $this->triggered;
    }

    public function trigger(): void
    {
        $this->triggered = true;
    }

    public function dispatch(): void
    {
        // No-op
    }

    public function reset(): void
    {
        $this->triggered = false;
    }
}
