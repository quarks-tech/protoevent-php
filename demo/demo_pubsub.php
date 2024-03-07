<?php

require_once "../vendor/autoload.php";

use Example\Books\V1\BookCreatedEvent;
use Example\Books\V1\EventBus\Publisher\Publisher as BooksV1Publisher;
use Example\Books\V1\EventBus\Receiver\BookCreatedEventHandlerInterface;
use Example\Books\V1\EventBus\Receiver\Receiver as BooksV1Receiver;
use Quarks\EventBus\Dispatcher\Adapter\SymfonyEventDispatcherAdapter;
use Quarks\EventBus\Dispatcher\Dispatcher;
use Quarks\EventBus\Publisher;
use Quarks\EventBus\Receiver;
use Symfony\Component\EventDispatcher\EventDispatcher;

$config = require 'config.php';

$connection = new \Quarks\EventBus\Transport\AMQPConnection($config['amqp']);
$amqpTransport = new \Quarks\EventBus\Transport\AMQPTransport($connection, [
    'queue' => $config['protoevent']['queue'],
    'setupTopology' => true,
    'enableDlx' => true,
]);

$publisher = new Publisher($amqpTransport);

$booksV1Publisher = new BooksV1Publisher($publisher);
$booksV1Publisher->publishBookCreatedEvent(
    (new BookCreatedEvent())
        ->setId(312)
);

$eventDispatcher = new EventDispatcher();
$dispatcher = new Dispatcher(new SymfonyEventDispatcherAdapter($eventDispatcher));

$receiver = new Receiver($amqpTransport, $dispatcher);

$booksV1Receiver = new BooksV1Receiver($receiver, $dispatcher);
$booksV1Receiver->registerBookCreatedEventHandler(
    new class() implements BookCreatedEventHandlerInterface {
        public function handleBookCreatedEvent(BookCreatedEvent $event)
        {
            echo 'Received event: ' . $event->getId() . PHP_EOL;
        }
    }
);

function signalHandler($signal) {
    echo "Received shutdown signal. Quitting..." . PHP_EOL;

    exit(0);
}

pcntl_async_signals(true);
pcntl_signal(SIGINT, 'signalHandler');

$receiver->run();
