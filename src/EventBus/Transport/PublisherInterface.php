<?php

namespace Quarks\EventBus\Transport;

use Google\Protobuf\Internal\Message;
use Quarks\EventBus\Envelope;
use Quarks\EventBus\Metadata;

interface PublisherInterface
{
    public function publish(Envelope $envelope, array $options = []): void;
}
