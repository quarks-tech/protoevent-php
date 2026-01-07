<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use QuarksTech\ProtoEvent\Encoding\CodecRegistry;
use QuarksTech\ProtoEvent\EventBus\EventDesc;
use QuarksTech\ProtoEvent\EventBus\Publisher;
use QuarksTech\ProtoEvent\EventBus\PublishOption\WithSource;
use QuarksTech\ProtoEvent\EventBus\PublishOption\WithSubject;
use QuarksTech\ProtoEvent\EventBus\ServiceDesc;
use QuarksTech\ProtoEvent\EventBus\Subscriber;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpReceiverOptions;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpSenderOptions;
use QuarksTech\ProtoEvent\Tests\Integration\TestCodec;
use QuarksTech\ProtoEvent\Tests\Integration\TestEvent;
use QuarksTech\ProtoEvent\Tests\Integration\TestEventHandler;
use QuarksTech\ProtoEvent\Tests\Integration\TestEventHandlerImpl;

echo "=== ProtoEvent AMQP Integration Test ===\n\n";

// Connection settings from environment
$host = getenv('AMQP_HOST') ?: 'localhost';
$port = (int)(getenv('AMQP_PORT') ?: 5672);
$user = getenv('AMQP_USER') ?: 'guest';
$pass = getenv('AMQP_PASSWORD') ?: 'guest';
$vhost = getenv('AMQP_VHOST') ?: '/';

echo "Connecting to RabbitMQ at {$host}:{$port}...\n";

// Create connections using fully qualified class name
$publisherConnection = new \QuarksTech\ProtoEvent\Transport\Amqp\AmqpConnection(
    $host, $port, $user, $pass, $vhost
);

$subscriberConnection = new \QuarksTech\ProtoEvent\Transport\Amqp\AmqpConnection(
    $host, $port, $user, $pass, $vhost
);

// Create test codec
$testCodec = new TestCodec();

// Create ServiceDesc for our test events
$serviceDesc = new ServiceDesc(
    'test.events.v1',
    [
        new EventDesc(
            'TestCreated',
            TestEvent::class,
            'handleTestCreatedEvent',
            TestEventHandler::class
        ),
    ],
    'test.proto'
);

// ==================== PUBLISHER SETUP ====================
echo "Setting up publisher...\n";

$senderOptions = new AmqpSenderOptions(
    deliveryMode: AmqpSenderOptions::DELIVERY_MODE_PERSISTENT,
    setupTopology: true
);

$sender = new \QuarksTech\ProtoEvent\Transport\Amqp\AmqpSender(
    $publisherConnection,
    $senderOptions
);
$sender->setup($serviceDesc);

$publisher = new Publisher($sender, $testCodec);
$publisher->withDefaultOptions(new WithSource('integration-test'));

// ==================== SUBSCRIBER SETUP ====================
echo "Setting up subscriber...\n";

$codecRegistry = new CodecRegistry();
$codecRegistry->register($testCodec);

$receiverOptions = new AmqpReceiverOptions(
    prefetchCount: 1,
    setupTopology: true,
    setupBindings: true
);

$receiver = new \QuarksTech\ProtoEvent\Transport\Amqp\AmqpReceiver(
    $subscriberConnection,
    $receiverOptions
);

$subscriber = new Subscriber('test-consumer', $codecRegistry);

// Create and register handler
$handler = new TestEventHandlerImpl();
$subscriber->registerEventHandler($serviceDesc, 'TestCreated', $handler);

// IMPORTANT: Setup receiver topology/bindings BEFORE publishing
// Otherwise the queue doesn't exist yet and messages are dropped
$receiver->setup('test-consumer', $subscriber->getServiceInfos());

// ==================== PUBLISH TEST EVENT ====================
echo "\n--- Publishing test event ---\n";

$event = new TestEvent();
$event->setId('test-123');
$event->setName('Integration Test Event');

$publisher->publish(
    'test.events.v1.TestCreated',
    $event,
    new WithSubject('test-subject')
);

echo "Published event: id={$event->getId()}, name={$event->getName()}\n";

// ==================== CONSUME (with timeout) ====================
echo "\n--- Consuming events (will stop after receiving one) ---\n";

// Fork a process to stop the consumer after timeout
if (function_exists('pcntl_fork')) {
    $pid = pcntl_fork();
    if ($pid === 0) {
        // Child process: wait and send SIGTERM
        sleep(5);
        posix_kill(posix_getppid(), SIGTERM);
        exit(0);
    }
}

// Use receive directly since we already called setup
try {
    $receiver->receive(function ($metadata, $data) use ($subscriber, $handler, $codecRegistry) {
        // Get codec for this content type
        $codec = $codecRegistry->getCodec($metadata->getDataContentType());

        // Decode and invoke handler
        $event = $codec->decode($data, TestEvent::class);
        $handler->handleTestCreatedEvent(new \QuarksTech\ProtoEvent\EventBus\EventContext($metadata), $event);
    });
} catch (Throwable $e) {
    echo "Consumer stopped: {$e->getMessage()}\n";
}

// Clean up child process
if (isset($pid) && $pid > 0) {
    pcntl_waitpid($pid, $status, WNOHANG);
    posix_kill($pid, SIGKILL);
}

echo "\n=== Test completed ===\n";

if ($handler->eventReceived) {
    echo "SUCCESS: Event was received and processed!\n";
} else {
    echo "TIMEOUT: No event received within timeout period\n";
}

echo "Check RabbitMQ admin at http://localhost:15672 (guest/guest)\n";
