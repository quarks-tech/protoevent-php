<?php

namespace Quarks\EventBus\Transport;

use Quarks\EventBus\Message;

interface TransportInterface extends PublisherInterface, ConfirmableInterface, SetupInterface
{
    /** @return array<Message> */
    public function get(): iterable;
}
