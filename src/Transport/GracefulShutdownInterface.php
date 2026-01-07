<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport;

/**
 * Interface for graceful shutdown handling
 *
 * Implementations can use pcntl_signal, Seld\Signal\SignalHandler,
 * or any other mechanism to detect shutdown signals.
 */
interface GracefulShutdownInterface
{
    /**
     * Check if shutdown was requested (SIGTERM/SIGINT received or trigger() called)
     */
    public function isTriggered(): bool;

    /**
     * Programmatically trigger shutdown
     *
     * Use this to stop consumers from code without sending a signal.
     */
    public function trigger(): void;

    /**
     * Dispatch pending signals (for pcntl-based implementations)
     *
     * This should be called periodically in consume loops to process
     * any pending signals. No-op for implementations that use async signals.
     */
    public function dispatch(): void;

    /**
     * Reset the triggered state
     */
    public function reset(): void;
}
