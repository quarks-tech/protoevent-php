<?php

declare(strict_types=1);

/**
 * Example: Publishing events to RabbitMQ
 *
 * This example demonstrates how to:
 * - Create an AMQP connection
 * - Setup a sender with topology
 * - Publish events using the Publisher
 */

require_once __DIR__ . '/../vendor/autoload.php';

use QuarksTech\ProtoEvent\EventBus\Publisher;
use QuarksTech\ProtoEvent\EventBus\PublishOption\WithSource;
use QuarksTech\ProtoEvent\EventBus\PublishOption\WithSubject;
use QuarksTech\ProtoEvent\EventBus\PublishOption\WithExtension;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpConnection;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpSender;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpSenderOptions;

// Assuming you have generated proto classes in this namespace
// use Example\Books\V1\BookCreatedEvent;
// use Example\Books\V1\EventBus\ServiceDesc;

// Create AMQP connection
$connection = new AmqpConnection(
    getenv('AMQP_HOST') ?: 'localhost',
    (int)(getenv('AMQP_PORT') ?: 5672),
    getenv('AMQP_USER') ?: 'guest',
    getenv('AMQP_PASSWORD') ?: 'guest',
    getenv('AMQP_VHOST') ?: '/'
);

// Or use DSN
// $connection = AmqpConnection::fromDsn('amqp://guest:guest@localhost:5672/');

// Create sender with options
$senderOptions = new AmqpSenderOptions(
    AmqpSenderOptions::DELIVERY_MODE_PERSISTENT,
    true // setupTopology
);

$sender = new AmqpSender($connection, $senderOptions);

// Setup the exchange (call once per service)
// $sender->setup(ServiceDesc::get());

// Create publisher
$publisher = new Publisher($sender);

// Add default options if needed
$publisher->withDefaultOptions(
    new WithSource('my-service')
);

// Publish an event
// $event = new BookCreatedEvent();
// $event->setId(1);
// $event->setTitle('Example Book');

// Using generated typed publisher:
// $typedPublisher = new \Example\Books\V1\EventBus\Publisher($publisher);
// $typedPublisher->publishBookCreatedEvent($event);

// Or using base publisher directly:
// $publisher->publish(
//     'example.books.v1.BookCreated',
//     $event,
//     new WithSubject('book-123'),
//     new WithExtension('correlationId', 'abc-123')
// );

echo "Publisher example - see comments for usage\n";
