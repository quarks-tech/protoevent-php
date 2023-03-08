<?php

namespace Quarks\EventBus\Encoding;

use Quarks\EventBus\CloudEvent;
use Quarks\EventBus\Exception\MessageDecodingFailedException;
use Quarks\EventBus\Exception\MessageEncodingFailedException;

class ProtoJsonEncoder implements EncoderInterface, DecoderInterface
{
    private const TIME_ZONE = 'UTC';

    public function encode(CloudEvent $event)
    {
        $plain = [
            'source' => $event->getSource(),
            'data' => $event->getData(),
            'datacontenttype' => $event->getDataContentType(),
            'time' => $event->getTime()->format(\DateTimeImmutable::RFC3339),
            'specversion' => $event->getSpecVersion(),
            'id' => $event->getId(),
            'type' => $event->getType(),
        ];

        try {
            return json_encode($plain, JSON_THROW_ON_ERROR);
        } catch (\Exception $exception) {
            throw new MessageEncodingFailedException('', 0, $exception);
        }
    }

    public function decode($encoded): CloudEvent
    {
        try {
            $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                throw new MessageDecodingFailedException();
            }

            return new CloudEvent(
                $decoded['id'],
                $decoded['source'],
                $decoded['type'],
                $decoded['data'],
                $decoded['datacontenttype'],
                \DateTimeImmutable::createFromFormat(
                    \DateTimeImmutable::RFC3339,
                    $decoded['time'],
                    new \DateTimeZone(self::TIME_ZONE),
                )
            );
        } catch (\Exception $exception) {
            throw new MessageDecodingFailedException('', 0, $exception);
        }
    }
}
