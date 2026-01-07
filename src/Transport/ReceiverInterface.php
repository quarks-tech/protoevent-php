<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport;

use QuarksTech\ProtoEvent\Event\Metadata;

/**
 * Interface for receiving messages from a transport
 */
interface ReceiverInterface
{
    /**
     * Start receiving messages
     *
     * This method blocks until the receiver is stopped.
     *
     * @param callable(Metadata, string): void $processor Callback to process received messages
     */
    public function receive(callable $processor): void;

    /**
     * Stop receiving messages
     */
    public function stop(): void;
}
