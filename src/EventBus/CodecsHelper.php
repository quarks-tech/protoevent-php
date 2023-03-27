<?php

namespace Quarks\EventBus;

use Google\Protobuf\Internal\Message;
use Quarks\EventBus\Encoding\Codecs\JsonCodec;
use Quarks\EventBus\Encoding\Codecs\ProtoCodec;
use Quarks\EventBus\Exception\UnsupportedContentType;

class CodecsHelper
{
    public static function encodeWithCodec(Message $event, string $contentSubType): mixed
    {
        switch ($contentSubType) {
            case ProtoCodec::NAME:
                return ProtoCodec::marshal($event);
            case JsonCodec::NAME:
                return JsonCodec::marshal($event);
        }

        throw new UnsupportedContentType();
    }

    public static function decodeWithCodec(Message $message, mixed $body, string $contentSubType)
    {
        switch ($contentSubType) {
            case ProtoCodec::NAME:
                return ProtoCodec::unmarshal($message, $body);
            case JsonCodec::NAME:
                return JsonCodec::unmarshal($message, $body);
        }

        throw new UnsupportedContentType();
    }
}
