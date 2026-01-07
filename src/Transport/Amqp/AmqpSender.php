<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport\Amqp;

use AMQPChannel;
use AMQPExchange;
use QuarksTech\ProtoEvent\Event\Metadata;
use QuarksTech\ProtoEvent\EventBus\ServiceDesc;
use QuarksTech\ProtoEvent\Exception\TransportException;
use QuarksTech\ProtoEvent\Transport\Amqp\Marshaler\Marshaler;
use QuarksTech\ProtoEvent\Transport\Amqp\Marshaler\MarshalerInterface;
use QuarksTech\ProtoEvent\Transport\SenderInterface;
use Throwable;

/**
 * AMQP sender implementation using topic exchanges
 */
final class AmqpSender implements SenderInterface
{
    private AMQPChannel $channel;
    private AMQPExchange $exchange;
    private MarshalerInterface $marshaler;
    private AmqpSenderOptions $options;

    public function __construct(
        AmqpConnection $connection,
        ?AmqpSenderOptions $options = null,
        ?MarshalerInterface $marshaler = null,
    ) {
        $connection->connect();

        $this->channel = new AMQPChannel($connection->getConnection());
        $this->exchange = new AMQPExchange($this->channel);
        $this->options = $options ?? new AmqpSenderOptions();
        $this->marshaler = $marshaler ?? new Marshaler();
    }

    public function setup(ServiceDesc $serviceDesc): void
    {
        if (!$this->options->shouldSetupTopology()) {
            return;
        }

        try {
            $this->exchange->setName($serviceDesc->getServiceName());
            $this->exchange->setType(AMQP_EX_TYPE_TOPIC);
            $this->exchange->setFlags(AMQP_DURABLE);
            $this->exchange->declareExchange();
        } catch (Throwable $e) {
            throw new TransportException(
                "Failed to setup exchange '{$serviceDesc->getServiceName()}': " . $e->getMessage(),
                previous: $e,
            );
        }
    }

    public function send(Metadata $metadata, string $data): void
    {
        try {
            $message = $this->marshaler->marshal($metadata, $data);

            $exchangeName = $metadata->getServiceName();
            $routingKey = $metadata->getEventName();

            $this->exchange->setName($exchangeName);

            $attributes = array_merge($message['attributes'], [
                'delivery_mode' => $this->options->getDeliveryMode(),
            ]);

            $this->exchange->publish(
                $message['body'],
                $routingKey,
                AMQP_NOPARAM,
                $attributes,
            );
        } catch (Throwable $e) {
            throw new TransportException(
                'Failed to send message: ' . $e->getMessage(),
                previous: $e,
            );
        }
    }
}
