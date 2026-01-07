<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Tests\Integration;

use Google\Protobuf\Internal\Message;
use QuarksTech\ProtoEvent\Encoding\CodecInterface;

/**
 * Simple JSON codec for testing
 */
class TestCodec implements CodecInterface
{
    public function encode(Message $message): string
    {
        if ($message instanceof TestEvent) {
            return json_encode([
                'id' => $message->getId(),
                'name' => $message->getName(),
            ]);
        }

        return $message->serializeToString();
    }

    public function decode(string $data, string $messageClass): Message
    {
        $decoded = json_decode($data, true);

        if ($messageClass === TestEvent::class) {
            $event = new TestEvent();
            $event->setId($decoded['id'] ?? '');
            $event->setName($decoded['name'] ?? '');
            return $event;
        }

        throw new \RuntimeException("Unknown message class: {$messageClass}");
    }

    public function getContentType(): string
    {
        return 'application/json';
    }
}
