<?php

namespace Quarks\EventBus\Transport;

use Quarks\EventBus\Message;

interface TransportInterface extends PublisherInterface, TransportWithSetupInterface, SetupInterface
{
    /** @return array<Message> */
    public function get(): iterable;
}
