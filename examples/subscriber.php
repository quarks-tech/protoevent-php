<?php

declare(strict_types=1);

/**
 * Example: Consuming events from RabbitMQ with parking lot pattern
 *
 * This example demonstrates how to:
 * - Create an AMQP connection
 * - Setup a subscriber with parking lot receiver
 * - Register event handlers
 * - Consume events with graceful shutdown
 */

require_once __DIR__ . '/../vendor/autoload.php';

use QuarksTech\ProtoEvent\EventBus\Subscriber;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpConnection;
use QuarksTech\ProtoEvent\Transport\Amqp\ParkingLotReceiver;
use QuarksTech\ProtoEvent\Transport\Amqp\ParkingLotReceiverOptions;

// Assuming you have generated proto classes and handlers
// use Example\Books\V1\EventBus\ServiceDesc;
// use Example\Books\V1\EventBus\Registration;

// Create AMQP connection
$connection = new AmqpConnection(
    host: getenv('AMQP_HOST') ?: 'localhost',
    port: (int)(getenv('AMQP_PORT') ?: 5672),
    user: getenv('AMQP_USER') ?: 'guest',
    password: getenv('AMQP_PASSWORD') ?: 'guest',
    vhost: getenv('AMQP_VHOST') ?: '/'
);

// Consumer name (used for queue name)
$consumerName = 'example.consumers.v1';

// Create parking lot receiver with retry logic
$receiverOptions = new ParkingLotReceiverOptions(
    queueName: $consumerName,
    prefetchCount: 3,
    maxRetries: 3,
    retryBackoffMs: 15000, // 15 seconds
    setupTopology: true,
    setupBindings: true
);

$receiver = new ParkingLotReceiver($connection, $receiverOptions);

// Create subscriber
$subscriber = new Subscriber($consumerName);

// Register event handlers
// class BookEventHandler implements \Example\Books\V1\EventBus\Handler\BookCreatedEventHandler
// {
//     public function handleBookCreatedEvent(EventContext $context, BookCreatedEvent $event): void
//     {
//         echo "Received book: " . $event->getTitle() . "\n";
//         echo "Event ID: " . $context->getEventId() . "\n";
//     }
// }

// $handler = new BookEventHandler();
// Registration::registerBookCreatedEventHandler($subscriber, $handler);

// Start consuming (blocks until SIGTERM/SIGINT)
echo "Starting consumer... Press Ctrl+C to stop.\n";
// $subscriber->subscribe($receiver);

echo "Subscriber example - see comments for usage\n";
