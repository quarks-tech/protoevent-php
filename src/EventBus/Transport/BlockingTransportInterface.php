<?php

namespace Quarks\EventBus\Transport;

interface BlockingTransportInterface extends PublisherInterface, TransportWithSetupInterface, SetupInterface
{
    public function fetch(callable $fetcher);
}
