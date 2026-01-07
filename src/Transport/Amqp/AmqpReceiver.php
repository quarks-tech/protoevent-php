<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport\Amqp;

use AMQPChannel;
use AMQPEnvelope;
use AMQPQueue;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use QuarksTech\ProtoEvent\EventBus\ServiceInfo;
use QuarksTech\ProtoEvent\Exception\TransportException;
use QuarksTech\ProtoEvent\Exception\UnprocessableEventException;
use QuarksTech\ProtoEvent\Transport\Amqp\Marshaler\Marshaler;
use QuarksTech\ProtoEvent\Transport\Amqp\Marshaler\MarshalerInterface;
use QuarksTech\ProtoEvent\Transport\GracefulShutdownInterface;
use QuarksTech\ProtoEvent\Transport\PcntlGracefulShutdown;
use QuarksTech\ProtoEvent\Transport\ReceiverInterface;
use QuarksTech\ProtoEvent\Transport\SetuperInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * AMQP receiver implementation using basic.consume
 *
 * Features:
 * - Uses basic.consume for proper consumer visibility in RabbitMQ
 * - Graceful shutdown via GracefulShutdownInterface
 * - QoS prefetch configuration
 */
final class AmqpReceiver implements ReceiverInterface, SetuperInterface
{
    private AmqpConnection $connection;
    private AMQPChannel $channel;
    private AMQPQueue $queue;
    private MarshalerInterface $marshaler;
    private LoggerInterface $logger;
    private AmqpReceiverOptions $options;
    private GracefulShutdownInterface $gracefulShutdown;

    private string $consumerTag = '';
    private string $queueName = '';
    private bool $isConsuming = false;

    public function __construct(
        AmqpConnection $connection,
        ?AmqpReceiverOptions $options = null,
        ?MarshalerInterface $marshaler = null,
        ?LoggerInterface $logger = null,
        ?GracefulShutdownInterface $gracefulShutdown = null,
    ) {
        $this->connection = $connection;
        $this->connection->connect();

        $this->channel = new AMQPChannel($this->connection->getConnection());
        $this->queue = new AMQPQueue($this->channel);
        $this->options = $options ?? new AmqpReceiverOptions();
        $this->marshaler = $marshaler ?? new Marshaler();
        $this->logger = $logger ?? new NullLogger();
        $this->gracefulShutdown = $gracefulShutdown ?? new PcntlGracefulShutdown($this->logger);
    }

    /**
     * @param ServiceInfo[] $serviceInfos
     */
    public function setup(string $consumerName, array $serviceInfos): void
    {
        $this->consumerTag = $consumerName . '-' . Uuid::uuid4()->toString();
        $this->queueName = $this->options->getQueueName() ?? $consumerName;

        if ($this->options->shouldSetupTopology()) {
            $this->setupTopology();
        }

        if ($this->options->shouldSetupBindings()) {
            $this->setupBindings($serviceInfos);
        }

        // Set QoS
        $this->channel->setPrefetchCount($this->options->getPrefetchCount());
    }

    private function setupTopology(): void
    {
        $this->queue->setName($this->queueName);
        $this->queue->setFlags(AMQP_DURABLE);
        $this->queue->declareQueue();
    }

    /**
     * @param ServiceInfo[] $serviceInfos
     */
    private function setupBindings(array $serviceInfos): void
    {
        $this->queue->setName($this->queueName);

        foreach ($serviceInfos as $serviceInfo) {
            foreach ($serviceInfo->getEvents() as $eventName) {
                $this->queue->bind($serviceInfo->getServiceName(), $eventName);
            }
        }
    }

    public function receive(callable $processor): void
    {
        if ($this->isConsuming) {
            throw new TransportException('Receiver is already consuming');
        }

        $this->isConsuming = true;
        $this->gracefulShutdown->reset();

        try {
            $this->queue->consume(
                function (AMQPEnvelope $envelope, AMQPQueue $queue) use ($processor): bool {
                    if ($this->gracefulShutdown->isTriggered()) {
                        // Requeue the message since we're shutting down
                        $queue->nack($envelope->getDeliveryTag(), AMQP_REQUEUE);
                        return false;
                    }

                    $this->processEnvelope($envelope, $queue, $processor);

                    // Dispatch signals
                    $this->gracefulShutdown->dispatch();

                    return !$this->gracefulShutdown->isTriggered();
                },
                AMQP_NOPARAM,
                $this->consumerTag,
            );
        } finally {
            $this->isConsuming = false;
            $this->cancelConsumer();
        }
    }

    private function processEnvelope(AMQPEnvelope $envelope, AMQPQueue $queue, callable $processor): void
    {
        try {
            $result = $this->marshaler->unmarshal($envelope);
            $processor($result['metadata'], $result['data']);

            // Acknowledge successful processing
            $queue->ack($envelope->getDeliveryTag());

        } catch (UnprocessableEventException $e) {
            $this->logger->error('Unprocessable event', [
                'error' => $e->getMessage(),
                'queue' => $this->queueName,
                'consumer_tag' => $this->consumerTag,
                'delivery_tag' => $envelope->getDeliveryTag(),
                'routing_key' => $envelope->getRoutingKey(),
                'content_type' => $envelope->getContentType(),
                'type' => $envelope->getType(),
            ]);

            // Reject without requeue
            $queue->reject($envelope->getDeliveryTag(), AMQP_NOPARAM);

        } catch (Throwable $e) {
            $this->logger->error('Error processing event', [
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'queue' => $this->queueName,
                'consumer_tag' => $this->consumerTag,
                'delivery_tag' => $envelope->getDeliveryTag(),
                'routing_key' => $envelope->getRoutingKey(),
            ]);

            if ($this->options->shouldRequeueOnError()) {
                $queue->nack($envelope->getDeliveryTag(), AMQP_REQUEUE);
            } else {
                $queue->reject($envelope->getDeliveryTag(), AMQP_NOPARAM);
            }
        }
    }

    private function cancelConsumer(): void
    {
        if ($this->consumerTag !== '' && $this->connection->isConnected()) {
            try {
                $this->queue->cancel($this->consumerTag);
            } catch (Throwable) {
                // Ignore cancellation errors during shutdown
            }
        }
    }

    public function stop(): void
    {
        $this->gracefulShutdown->trigger();
    }
}
