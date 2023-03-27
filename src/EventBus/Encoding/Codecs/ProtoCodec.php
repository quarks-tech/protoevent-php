<?php

namespace Quarks\EventBus\Encoding\Codecs;

use Google\Protobuf\Internal\Message;

class ProtoCodec implements CodecInterface
{
    public const NAME = 'proto';

    public static function marshal(Message $message): mixed
    {
        return $message->serializeToString();
    }

    public static function unmarshal(Message $message, mixed $data)
    {
        return $message->mergeFromJsonString($data);
    }
}
