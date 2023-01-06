<?php

namespace Quarks\EventBus\Transport;

interface BlockingTransportInterface extends PublisherInterface, ConfirmableInterface, SetupInterface
{
    public function fetch(callable $fetcher);
}
