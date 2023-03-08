<?php

namespace Quarks\EventBus\Encoding;

use Quarks\EventBus\CloudEvent;
use Quarks\EventBus\Exception\MessageDecodingFailedException;

interface DecoderInterface
{
    /**
     * @param $encoded
     * @return CloudEvent
     * @throws MessageDecodingFailedException
     */
    public function decode($encoded): CloudEvent;
}
