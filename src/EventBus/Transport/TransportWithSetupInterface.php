<?php

namespace Quarks\EventBus\Transport;

use Quarks\EventBus\Message;

interface TransportWithSetupInterface
{
    public function ack(Message $message): void;
    public function reject(Message $message, bool $requeue = false): void;
}
