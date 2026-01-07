<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Encoding;

use Google\Protobuf\Internal\Message;
use QuarksTech\ProtoEvent\Exception\EncodingException;
use Throwable;

/**
 * Codec for JSON format using protobuf's JSON serialization
 */
final class JsonCodec implements CodecInterface
{
    public function encode(Message $message): string
    {
        return $message->serializeToJsonString();
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
            // Use ignore_unknown to be forward compatible
            $message->mergeFromJsonString($data, true);
        } catch (Throwable $e) {
            throw new EncodingException(
                'Failed to decode JSON message: ' . $e->getMessage(),
                previous: $e,
            );
        }

        return $message;
    }

    public function getContentType(): string
    {
        return 'application/json';
    }
}
