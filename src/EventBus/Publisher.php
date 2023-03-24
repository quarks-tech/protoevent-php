<?php

namespace Quarks\EventBus;

use Google\Protobuf\Internal\Message;
use Quarks\EventBus\Exception\PublisherException;
use Quarks\EventBus\Transport\TransportInterface;
use Ramsey\Uuid\Uuid;

class Publisher
{
    private TransportInterface $transport;
    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @throws PublisherException
     */
    public function publish(Message $event, string $eventName, array $options = []): void
    {
        try {
            $metadata = new Metadata(
                '1.0',
                $eventName,
                $options['source'] ?? 'protoevent-php',
                $options['id'] ?? Uuid::uuid4()->toString(),
                $options['time'] ?? date(\DateTimeInterface::RFC3339)
            );
            $metadata->setDataContentType(
                $options['dataContentType'] ?? 'application/cloudevents+json'
            );

            $this->transport->publish(
                new Envelope(
                    $metadata,
                    CodecsHelper::encodeWithCodec($event, ContentTypeHelper::extractSubType($metadata->getDataContentType()))
                )
            );
        } catch (\Exception $exception) {
            throw new PublisherException('', 0, $exception);
        }
    }

}
