<?php

namespace Quarks\EventBus\Encoding;

use CloudEvents\CloudEventInterface;
use Quarks\EventBus\Exception\MessageDecodingFailedException;

interface DecoderInterface
{
    /**
     * @param $encoded
     * @return CloudEventInterface
     * @throws MessageDecodingFailedException
     */
    public function decode($encoded): CloudEventInterface;
}
