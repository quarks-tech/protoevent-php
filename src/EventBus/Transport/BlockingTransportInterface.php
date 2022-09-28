<?php

namespace Quarks\EventBus\Transport;

interface BlockingTransportInterface
{
    public function fetch(callable $fetcher);
}
