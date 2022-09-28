<?php

namespace Quarks\EventBus\Encoding;

use CloudEvents\CloudEventInterface;
use CloudEvents\Serializers\JsonDeserializer;
use CloudEvents\Serializers\JsonSerializer;
use Quarks\EventBus\Exception\MessageDecodingFailedException;
use Quarks\EventBus\Exception\MessageEncodingFailedException;

class ProtoJsonEncoder implements EncoderInterface, DecoderInterface
{
    private JsonSerializer $serializer;
    private JsonDeserializer $deserializer;

    public function __construct()
    {
        $this->serializer = JsonSerializer::create();
        $this->deserializer = JsonDeserializer::create();
    }

    public function encode(CloudEventInterface $event)
    {
        try {
            return $this->serializer->serializeStructured($event);
        } catch (\Exception $exception) {
            throw new MessageEncodingFailedException('', 0, $exception);
        }
    }

    public function decode($encoded): CloudEventInterface
    {
        try {
            return $this->deserializer->deserializeStructured($encoded);
        } catch (\Exception $exception) {
            throw new MessageDecodingFailedException('', 0, $exception);
        }
    }
}
