<?php

namespace Quarks\EventBus\Encoding;

use CloudEvents\V1\CloudEventInterface;
use Quarks\EventBus\Exception\MessageEncodingFailedException;

interface EncoderInterface
{
    /**
     * @param CloudEventInterface $event
     * @return mixed
     * @throws MessageEncodingFailedException
     */
    public function encode(CloudEventInterface $event);
}
