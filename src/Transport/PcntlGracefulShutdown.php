<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Graceful shutdown implementation using pcntl_signal
 *
 * Handles SIGTERM and SIGINT signals for graceful consumer shutdown.
 * Falls back to no-op if pcntl extension is not available.
 */
final class PcntlGracefulShutdown implements GracefulShutdownInterface
{
    private bool $triggered = false;
    private LoggerInterface $logger;
    private bool $registered = false;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->register();
    }

    private function register(): void
    {
        if ($this->registered) {
            return;
        }

        if (!function_exists('pcntl_signal')) {
            $this->logger->warning('pcntl extension not available, graceful shutdown disabled');
            return;
        }

        pcntl_signal(SIGTERM, function (): void {
            $this->logger->info('Received SIGTERM, initiating graceful shutdown');
            $this->triggered = true;
        });

        pcntl_signal(SIGINT, function (): void {
            $this->logger->info('Received SIGINT, initiating graceful shutdown');
            $this->triggered = true;
        });

        $this->registered = true;
    }

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
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
    }

    public function reset(): void
    {
        $this->triggered = false;
    }
}
