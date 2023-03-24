<?php

namespace Quarks\EventBus\Encoding\Codecs;

use Google\Protobuf\Internal\Message;

interface CodecInterface
{
    public static function marshal(Message $message): mixed;
    public static function unmarshal(Message $message, mixed $data);
}
