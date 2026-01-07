<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport\Amqp;

use AMQPChannel;
use AMQPEnvelope;
use AMQPExchange;
use AMQPQueue;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use QuarksTech\ProtoEvent\EventBus\ServiceInfo;
use QuarksTech\ProtoEvent\Exception\UnprocessableEventException;
use QuarksTech\ProtoEvent\Exception\TransportException;
use QuarksTech\ProtoEvent\Transport\Amqp\Marshaler\Marshaler;
use QuarksTech\ProtoEvent\Transport\Amqp\Marshaler\MarshalerInterface;
use QuarksTech\ProtoEvent\Transport\GracefulShutdownInterface;
use QuarksTech\ProtoEvent\Transport\PcntlGracefulShutdown;
use QuarksTech\ProtoEvent\Transport\ReceiverInterface;
use QuarksTech\ProtoEvent\Transport\SetuperInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * AMQP Receiver with parking lot pattern for retry logic
 *
 * Flow:
 * Main Queue → DLX (on reject) → Wait Queue (TTL) → DLX (on expire) → Main Queue (retry)
 * After max retries → Parking Lot Queue
 *
 * Queue naming:
 * - {consumer}      - main queue
 * - {consumer}.wait - wait queue with TTL
 * - {consumer}.pl   - parking lot queue
 * - {consumer}.dlx  - dead letter exchange
 */
final class ParkingLotReceiver implements ReceiverInterface, SetuperInterface
{
    private const DLX_SUFFIX = '.dlx';
    private const WAIT_SUFFIX = '.wait';
    private const PL_SUFFIX = '.pl';

    private const ROUTING_KEY_WAIT = 'wait';
    private const ROUTING_KEY_RETRY = 'retry';
    private const ROUTING_KEY_PARKING_LOT = 'parkingLot';

    private AmqpConnection $connection;
    private AMQPChannel $channel;
    private AMQPQueue $queue;
    private AMQPExchange $dlxExchange;
    private MarshalerInterface $marshaler;
    private LoggerInterface $logger;
    private ParkingLotReceiverOptions $options;
    private GracefulShutdownInterface $gracefulShutdown;

    private string $consumerTag = '';
    private string $queueName = '';
    private string $dlxExchangeName = '';
    private bool $isConsuming = false;

    public function __construct(
        AmqpConnection $connection,
        ParkingLotReceiverOptions $options,
        ?MarshalerInterface $marshaler = null,
        ?LoggerInterface $logger = null,
        ?GracefulShutdownInterface $gracefulShutdown = null,
    ) {
        $this->connection = $connection;
        $this->connection->connect();

        $this->channel = new AMQPChannel($this->connection->getConnection());
        $this->queue = new AMQPQueue($this->channel);
        $this->dlxExchange = new AMQPExchange($this->channel);
        $this->options = $options;
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
        $this->queueName = $this->options->getQueueName();
        $this->dlxExchangeName = $this->queueName . self::DLX_SUFFIX;

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
        $waitQueueName = $this->queueName . self::WAIT_SUFFIX;
        $parkingLotQueueName = $this->queueName . self::PL_SUFFIX;

        // Create DLX exchange (topic for routing flexibility)
        $this->dlxExchange->setName($this->dlxExchangeName);
        $this->dlxExchange->setType(AMQP_EX_TYPE_TOPIC);
        $this->dlxExchange->setFlags(AMQP_DURABLE);
        $this->dlxExchange->declareExchange();

        // Create wait queue with TTL that routes back to main queue on expire
        $waitQueue = new AMQPQueue($this->channel);
        $waitQueue->setName($waitQueueName);
        $waitQueue->setFlags(AMQP_DURABLE);
        $waitQueue->setArgument('x-dead-letter-exchange', $this->dlxExchangeName);
        $waitQueue->setArgument('x-dead-letter-routing-key', self::ROUTING_KEY_RETRY);
        $waitQueue->setArgument('x-message-ttl', $this->options->getRetryBackoffMs());
        $waitQueue->declareQueue();
        $waitQueue->bind($this->dlxExchangeName, self::ROUTING_KEY_WAIT);

        // Create parking lot queue for permanently failed messages
        $parkingLotQueue = new AMQPQueue($this->channel);
        $parkingLotQueue->setName($parkingLotQueueName);
        $parkingLotQueue->setFlags(AMQP_DURABLE);
        $parkingLotQueue->declareQueue();
        $parkingLotQueue->bind($this->dlxExchangeName, self::ROUTING_KEY_PARKING_LOT);

        // Create main queue with DLX routing to wait queue
        $this->queue->setName($this->queueName);
        $this->queue->setFlags(AMQP_DURABLE);
        $this->queue->setArgument('x-dead-letter-exchange', $this->dlxExchangeName);
        $this->queue->setArgument('x-dead-letter-routing-key', self::ROUTING_KEY_WAIT);
        $this->queue->declareQueue();

        // Bind main queue to receive retried messages
        $this->queue->bind($this->dlxExchangeName, self::ROUTING_KEY_RETRY);
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
                        $queue->nack($envelope->getDeliveryTag(), AMQP_REQUEUE);
                        return false;
                    }

                    $this->processEnvelope($envelope, $queue, $processor);

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

            $queue->ack($envelope->getDeliveryTag());

        } catch (UnprocessableEventException $e) {
            $this->logger->warning('Unprocessable event, sending to parking lot', [
                'error' => $e->getMessage(),
                'routing_key' => $envelope->getRoutingKey(),
            ]);

            $this->putIntoParkingLot($envelope, $queue);

        } catch (Throwable $e) {
            $retryCount = $this->getRetryCount($envelope);

            if ($retryCount >= $this->options->getMaxRetries()) {
                $this->logger->error('Max retries exceeded, sending to parking lot', [
                    'error' => $e->getMessage(),
                    'retry_count' => $retryCount,
                    'routing_key' => $envelope->getRoutingKey(),
                ]);

                $this->putIntoParkingLot($envelope, $queue);
            } else {
                $this->logger->warning('Processing failed, will retry', [
                    'error' => $e->getMessage(),
                    'retry_count' => $retryCount,
                    'max_retries' => $this->options->getMaxRetries(),
                    'routing_key' => $envelope->getRoutingKey(),
                ]);

                // Reject without requeue - goes to wait queue via DLX
                $queue->reject($envelope->getDeliveryTag(), AMQP_NOPARAM);
            }
        }
    }

    private function getRetryCount(AMQPEnvelope $envelope): int
    {
        /** @var array<string, mixed> $headers */
        $headers = $envelope->getHeaders() ?? [];

        if (!isset($headers['x-death']) || !is_array($headers['x-death'])) {
            return 0;
        }

        // Try to find retry count from x-first-death-queue (most reliable)
        $firstDeathQueue = $headers['x-first-death-queue'] ?? null;

        if ($firstDeathQueue !== null) {
            foreach ($headers['x-death'] as $death) {
                if (is_object($death)) {
                    $death = get_object_vars($death);
                }

                if (is_array($death) && isset($death['queue']) && $death['queue'] === $firstDeathQueue) {
                    return (int) ($death['count'] ?? 0);
                }
            }
        }

        // Fallback: count rejections from our queue name
        foreach ($headers['x-death'] as $death) {
            if (is_object($death)) {
                $death = get_object_vars($death);
            }

            if (is_array($death) && isset($death['queue']) && $death['queue'] === $this->queueName) {
                return (int) ($death['count'] ?? 0);
            }
        }

        // Last resort: use the first x-death entry's count
        $firstDeath = reset($headers['x-death']);
        if (is_object($firstDeath)) {
            $firstDeath = get_object_vars($firstDeath);
        }
        if (is_array($firstDeath) && isset($firstDeath['count'])) {
            return (int) $firstDeath['count'];
        }

        return 0;
    }

    private function putIntoParkingLot(AMQPEnvelope $envelope, AMQPQueue $queue): void
    {
        // Publish to parking lot via DLX
        $this->dlxExchange->publish(
            $envelope->getBody(),
            self::ROUTING_KEY_PARKING_LOT,
            AMQP_NOPARAM,
            [
                'headers' => $envelope->getHeaders() ?? [],
                'content_type' => $envelope->getContentType(),
                'type' => $envelope->getType(),
                'delivery_mode' => $envelope->getDeliveryMode(),
            ],
        );

        // Ack the original message to remove from queue
        $queue->ack($envelope->getDeliveryTag());
    }

    private function cancelConsumer(): void
    {
        if ($this->consumerTag !== '' && $this->connection->isConnected()) {
            try {
                $this->queue->cancel($this->consumerTag);
            } catch (Throwable) {
                // Ignore
            }
        }
    }

    public function stop(): void
    {
        $this->gracefulShutdown->trigger();
    }
}
