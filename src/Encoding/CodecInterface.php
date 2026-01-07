<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Encoding;

use Google\Protobuf\Internal\Message;

/**
 * Interface for encoding/decoding protobuf messages
 */
interface CodecInterface
{
    /**
     * Encode a protobuf message to bytes
     */
    public function encode(Message $message): string;

    /**
     * Decode bytes to a protobuf message
     *
     * @template T of Message
     * @param class-string<T> $messageClass
     * @return T
     */
    public function decode(string $data, string $messageClass): Message;

    /**
     * Get the content type for this codec
     */
    public function getContentType(): string;
}
