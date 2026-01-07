<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport;

use QuarksTech\ProtoEvent\Event\Metadata;
use QuarksTech\ProtoEvent\EventBus\ServiceDesc;

/**
 * Interface for sending messages to a transport
 */
interface SenderInterface
{
    /**
     * Send a message
     *
     * @param Metadata $metadata Event metadata
     * @param string $data Encoded message data
     */
    public function send(Metadata $metadata, string $data): void;

    /**
     * Setup the transport for the given service (e.g., create exchanges)
     */
    public function setup(ServiceDesc $serviceDesc): void;
}
