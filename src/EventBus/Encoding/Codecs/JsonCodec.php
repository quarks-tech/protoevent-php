<?php

namespace Quarks\EventBus\Encoding\Codecs;

use Google\Protobuf\Internal\Message;

class JsonCodec implements CodecInterface
{
    public const NAME = 'json';

    public static function marshal(Message $message): mixed
    {
        return $message->serializeToJsonString();
    }

    /**
     * Parses a json string to protobuf message, ignoring unknown fields.
     */
    public static function unmarshal(Message $message, mixed $data)
    {
        return $message->mergeFromJsonString($data, true);
    }
}
