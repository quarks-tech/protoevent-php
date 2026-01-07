# ProtoEvent PHP

PHP implementation for ProtoEvent with CloudEvents 1.0 and AMQP support.

## Features

- **CloudEvents 1.0 compliant** - Full support for CloudEvents metadata
- **Binary and Structured content modes** - Flexible message marshaling
- **Parking lot pattern** - Automatic retries with exponential backoff
- **Graceful shutdown** - Handles SIGTERM/SIGINT signals
- **Symfony integration** - Optional bundle for auto-wiring and console commands
- **Code generation** - protoc plugin generates type-safe publishers and handlers
- **In-memory transport** - For testing without RabbitMQ
- **Interceptors** - Middleware support for publishers and subscribers

## Requirements

- PHP 8.0+
- ext-amqp (php-amqp extension)
- RabbitMQ 3.x

## Installation

```bash
composer require quarks-tech/protoevent-php
```

For Symfony, also install the required dependencies:

```bash
composer require symfony/config symfony/dependency-injection symfony/console symfony/http-kernel
```

## Quick Start

### Publishing Events

```php
use QuarksTech\ProtoEvent\EventBus\Publisher;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpConnection;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpSender;

$connection = new AmqpConnection('localhost', 5672, 'guest', 'guest');
$sender = new AmqpSender($connection);
$publisher = new Publisher($sender);

// Setup exchange (once per service)
$sender->setup($serviceDesc);

// Publish using generated typed publisher
$typedPublisher = new \Your\Namespace\EventBus\Publisher($publisher);
$typedPublisher->publishYourEvent($event);
```

### Consuming Events

```php
use QuarksTech\ProtoEvent\EventBus\Subscriber;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpConnection;
use QuarksTech\ProtoEvent\Transport\Amqp\ParkingLotReceiver;
use QuarksTech\ProtoEvent\Transport\Amqp\ParkingLotReceiverOptions;

$connection = new AmqpConnection('localhost', 5672, 'guest', 'guest');

$receiver = new ParkingLotReceiver($connection, new ParkingLotReceiverOptions(
    queueName: 'my-consumer',
    maxRetries: 3,
    retryBackoffMs: 15000,
));

$subscriber = new Subscriber('my-consumer');

// Register handlers using generated registration functions
\Your\Namespace\EventBus\Registration::registerYourEventHandler($subscriber, $handler);

// Start consuming (blocks until signal)
$subscriber->subscribe($receiver);
```

## Code Generation

Install the protoc plugin:
```bash
go install github.com/quarks-tech/ddkitapis-generator-go/cmd/protoc-gen-php-eventbus@latest
```

Generate code:
```bash
protoc --php_out=./gen --php-eventbus_out=./gen your_events.proto
```

### Generated Files

For each proto file with event messages (marked with `(quarks_tech.protoevent.v1.enabled) = true`):

- `EventBus/ServiceDesc.php` - Service descriptor
- `EventBus/EventPublisher.php` - Typed publisher interface
- `EventBus/Publisher.php` - Publisher implementation
- `EventBus/Registration.php` - Handler registration functions
- `EventBus/Handler/*EventHandler.php` - Handler interfaces

## Symfony Integration

### Bundle Registration

```php
// config/bundles.php
return [
    // ...
    QuarksTech\ProtoEvent\SymfonyBundle\QuarksTechProtoEventBundle::class => ['all' => true],
];
```

### Configuration

```yaml
# config/packages/quarks_tech_proto_event.yaml
quarks_tech_proto_event:
    connection:
        dsn: '%env(RABBITMQ_DSN)%'

    queues:
        monolith.v2.subscriptions:
            parking_lot: true
            max_retries: 3

        monolith.v2.notifications:
            parking_lot: false
            prefetch_count: 10
```

### Event Handlers

One handler class per event. Just implement the generated interface:

```php
class SubscriptionCreatedHandler implements SubscriptionCreatedEventHandler
{
    public function handleSubscriptionCreatedEvent(EventContext $ctx, SubscriptionCreatedEvent $event): void
    {
        // Handle subscription created
    }
}

class SubscriptionUpdatedHandler implements SubscriptionUpdatedEventHandler
{
    public function handleSubscriptionUpdatedEvent(EventContext $ctx, SubscriptionUpdatedEvent $event): void
    {
        // Handle subscription updated
    }
}
```

Handlers are auto-discovered. Events are routed based on type.

### Console Commands

Run consumers for specific queues (ideal for Kubernetes deployments):

```bash
# Consumes SubscriptionCreated and SubscriptionUpdated events
php bin/console protoevent:consume monolith.v2.subscriptions

# Run in another pod
php bin/console protoevent:consume monolith.v2.notifications
```

All registered handlers are available to all queues. The subscriber automatically routes incoming events to the matching handler.

## Testing with InMemoryTransport

Use the in-memory transport for unit and integration tests:

```php
use QuarksTech\ProtoEvent\EventBus\Publisher;
use QuarksTech\ProtoEvent\EventBus\Subscriber;
use QuarksTech\ProtoEvent\Transport\InMemory\InMemoryTransport;

// Create shared transport
$transport = new InMemoryTransport();

// Publisher uses transport as sender
$publisher = new Publisher($transport);

// Subscriber uses transport as receiver
$subscriber = new Subscriber('test-consumer');

// Publish events
$publisher->publish('example.v1.TestEvent', $event);

// Verify messages
$this->assertSame(1, $transport->count());
$messages = $transport->getMessages();

// Consume events
$subscriber->subscribe($transport);

// Clear for next test
$transport->clear();  // clears messages only
$transport->reset();  // clears messages and services
```

## Interceptors

Add cross-cutting concerns with interceptors:

```php
use QuarksTech\ProtoEvent\EventBus\PublisherInterceptor;
use QuarksTech\ProtoEvent\EventBus\SubscriberInterceptor;

// Publisher interceptor
class TracingPublisherInterceptor implements PublisherInterceptor
{
    public function intercept(Metadata $metadata, Message $event, callable $next): void
    {
        $metadata = $metadata->withExtension('traceid', $this->traceId);
        $next($metadata, $event);
    }
}

// Subscriber interceptor
class LoggingSubscriberInterceptor implements SubscriberInterceptor
{
    public function intercept(EventContext $context, mixed $event, callable $next): void
    {
        $this->logger->info('Processing: ' . $context->getMetadata()->getType());
        $next($context, $event);
    }
}

// Add to publisher/subscriber
$publisher->withInterceptor(new TracingPublisherInterceptor($tracer));
$subscriber->withInterceptor(new LoggingSubscriberInterceptor($logger));
```

## Architecture

### Parking Lot Pattern

Failed messages flow through a retry cycle:

```
Main Queue → DLX → Wait Queue (TTL) → DLX → Main Queue (retry)
                                         ↓
                        (max retries) → Parking Lot Queue
```

Queue naming:
- `{queue}` - main processing queue
- `{queue}.wait` - wait queue with TTL backoff
- `{queue}.pl` - parking lot for permanently failed messages
- `{queue}.dlx` - dead letter exchange

### Message Marshaling

**Binary mode** (default): CloudEvents attributes in AMQP headers, payload in body.

**Structured mode**: Full CloudEvents JSON document in message body (use content type `application/cloudevents+json`).

Notes:
- Setting `application/cloudevents+json` implies **JSON payload encoding** (protobuf JSON for protobuf messages), matching the Go implementation.
- Timestamps are emitted as **RFC3339 without fractional seconds** for cross-language compatibility.

## License

MIT
