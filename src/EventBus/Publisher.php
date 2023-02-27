<?php

namespace Quarks\EventBus;

use CloudEvents\V1\CloudEventImmutable;
use Google\Protobuf\Internal\Message;
use Quarks\EventBus\Encoding\EncoderInterface;
use Quarks\EventBus\Exception\PublisherException;
use Quarks\EventBus\Transport\TransportInterface;
use Ramsey\Uuid\Uuid;

class Publisher
{
    private const CLOUD_EVENTS_CONTENT_TYPE = 'application/cloudevents+json';

    private TransportInterface $transport;
    private EncoderInterface $encoder;

    public function __construct(TransportInterface $transport, EncoderInterface $encoder)
    {
        $this->transport = $transport;
        $this->encoder = $encoder;
    }

    /**
     * @throws PublisherException
     */
    public function publish(Message $event, string $eventName, array $options = []): void
    {
        try {
            $cloudEvent = new CloudEventImmutable(
                Uuid::uuid4()->toString(),
                'protoevent-php',
                $eventName,
                $event->serializeToJsonString(),
                self::CLOUD_EVENTS_CONTENT_TYPE,
                null,
                null,
                date_create_immutable()
            );

            $this->transport->publish($eventName, $this->encoder->encode($cloudEvent));
        } catch (\Exception $exception) {
            throw new PublisherException('', 0, $exception);
        }
    }
}
