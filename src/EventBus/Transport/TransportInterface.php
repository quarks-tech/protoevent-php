<?php

namespace Quarks\EventBus\Transport;

use Quarks\EventBus\Envelope;

interface TransportInterface extends PublisherInterface, ConfirmableInterface, SetupInterface
{
    /** @return iterable<Envelope> */
    public function get(): iterable;
}
