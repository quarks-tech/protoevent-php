<?php

require_once "../vendor/autoload.php";

use Example\Books\V1\BookCreatedEvent;
use Example\Books\V1\EventBus\Receiver\BookCreatedEventHandlerInterface;
use Quarks\EventBus\Dispatcher\Adapter\SymfonyEventDispatcherAdapter;
use Quarks\EventBus\Dispatcher\Dispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Example\Books\V1\EventBus\Receiver\Receiver as BooksV1Receiver;

$config = require 'config.php';

$eventDispatcher = new EventDispatcher();
$dispatcher = new Dispatcher(new SymfonyEventDispatcherAdapter($eventDispatcher));

$connection = new \Quarks\EventBus\Transport\AMQPConnection($config['amqp']);
$transport = new \Quarks\EventBus\Transport\AMQPTransport($connection, [
    'queue' => $config['protoevent']['queue'],
    'setupTopology' => true,
    'enableDLX' => true,
]);

$receiver = new \Quarks\EventBus\BlockingReceiver($transport, $dispatcher);

$booksV1Receiver = new BooksV1Receiver($receiver, $dispatcher);
$booksV1Receiver->registerBookCreatedEventHandler(
    new class() implements BookCreatedEventHandlerInterface {
        public function handleBookCreatedEvent(BookCreatedEvent $event)
        {
            echo "Got event: " . $event->getId() . PHP_EOL;
        }
});

function signalHandler($signal) {
    echo "Received shutdown signal. Quitting..." . PHP_EOL;

    exit(1);
}

pcntl_async_signals(true);
pcntl_signal(SIGINT, 'signalHandler');

$receiver->run();