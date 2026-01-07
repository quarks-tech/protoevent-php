<?php

declare(strict_types=1);

/**
 * Example: Publishing 100 SubscriptionDeletedEvent messages to RabbitMQ
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DDKit\Matchmaker\Subscription\V1Alpha1\Subscription;
use DDKit\Matchmaker\Subscription\V1Alpha1\Subscription\ActivityType;
use DDKit\Matchmaker\Subscription\V1Alpha1\Subscription\ActivityDirection;
use DDKit\Matchmaker\Subscription\V1Alpha1\SubscriptionDeletedEvent;
use DDKit\Matchmaker\Subscription\V1Alpha1\EventBus\Publisher as SubscriptionPublisher;
use DDKit\Matchmaker\Subscription\V1Alpha1\EventBus\ServiceDesc;
use Google\Protobuf\Timestamp;
use QuarksTech\ProtoEvent\EventBus\Publisher;
use QuarksTech\ProtoEvent\EventBus\PublishOption\WithSource;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpConnection;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpSender;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpSenderOptions;

echo "Connecting to RabbitMQ...\n";

// Create AMQP connection
$connection = new AmqpConnection(
    getenv('AMQP_HOST') ?: 'localhost',
    (int)(getenv('AMQP_PORT') ?: 5672),
    getenv('AMQP_USER') ?: 'guest',
    getenv('AMQP_PASSWORD') ?: 'guest',
    getenv('AMQP_VHOST') ?: '/'
);

// Create sender with topology setup
$senderOptions = new AmqpSenderOptions(
    AmqpSenderOptions::DELIVERY_MODE_PERSISTENT,
    true // setupTopology
);

$sender = new AmqpSender($connection, $senderOptions);

// Setup the exchange for this service
$sender->setup(ServiceDesc::get());

// Create base publisher
$basePublisher = new Publisher($sender);
$basePublisher->withDefaultOptions(
    new WithSource('subscription-deleted-example')
);

// Create typed publisher for subscription events
$publisher = new SubscriptionPublisher($basePublisher);

echo "Publishing 100 SubscriptionDeletedEvent messages...\n";

$startTime = microtime(true);

for ($i = 1; $i <= 100; $i++) {
    // Create subscription with sample data
    $subscription = new Subscription();
    $subscription->setId($i);
    $subscription->setUserId(1000 + $i);
    $subscription->setProductId(1);
    $subscription->setActivityType(ActivityType::ACTIVITY_TYPE_LIKE);
    $subscription->setDirection(ActivityDirection::ACTIVITY_DIRECTION_INCOMING);

    // Set timestamps
    $now = new Timestamp();
    $now->setSeconds(time());
    $subscription->setCreateTime($now);
    $subscription->setUpdateTime($now);

    // Create the event
    $event = new SubscriptionDeletedEvent();
    $event->setSubscription($subscription);

    // Publish the event
    $publisher->publishSubscriptionDeletedEvent($event);

    if ($i % 10 === 0) {
        echo "Published {$i}/100 events\n";
    }
}

$elapsed = microtime(true) - $startTime;

echo "\n";
echo "Successfully published 100 SubscriptionDeletedEvent messages!\n";
echo sprintf("Time elapsed: %.3f seconds\n", $elapsed);
echo sprintf("Rate: %.1f events/second\n", 100 / $elapsed);
echo "\n";
echo "Check RabbitMQ Management UI at http://localhost:15672\n";
echo "Login: guest / guest\n";
