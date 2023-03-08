<?php

namespace Quarks\EventBus\Encoding;

use Quarks\EventBus\CloudEvent;
use Quarks\EventBus\Exception\MessageEncodingFailedException;

interface EncoderInterface
{
    /**
     * @param CloudEvent $event
     * @return mixed
     * @throws MessageEncodingFailedException
     */
    public function encode(CloudEvent $event);
}
