<?php

namespace Quarks\EventBus\Transport;

interface PublisherInterface
{
    public function publish(string $eventName, $body, array $options = []): void;
}
