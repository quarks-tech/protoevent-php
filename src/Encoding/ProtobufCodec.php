<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Encoding;

use Google\Protobuf\Internal\Message;
use QuarksTech\ProtoEvent\Exception\EncodingException;
use Throwable;

/**
 * Codec for Protocol Buffers binary format
 */
final class ProtobufCodec implements CodecInterface
{
    public function encode(Message $message): string
    {
        return $message->serializeToString();
    }

    /**
     * @template T of Message
     * @param class-string<T> $messageClass
     * @return T
     */
    public function decode(string $data, string $messageClass): Message
    {
        /** @var T $message */
        $message = new $messageClass();

        try {
            $message->mergeFromString($data);
        } catch (Throwable $e) {
            throw new EncodingException(
                'Failed to decode protobuf message: ' . $e->getMessage(),
                previous: $e,
            );
        }

        return $message;
    }

    public function getContentType(): string
    {
        return 'application/proto';
    }
}
