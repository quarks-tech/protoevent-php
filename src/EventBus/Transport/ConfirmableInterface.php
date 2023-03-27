<?php

namespace Quarks\EventBus\Transport;

use Quarks\EventBus\Envelope;

interface ConfirmableInterface
{
    public function ack(Envelope $envelope): void;
    public function reject(Envelope $envelope, bool $requeue = false): void;
}
