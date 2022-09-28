<?php

require_once "../vendor/autoload.php";

use Example\Books\V1\BookCreatedEvent;
use Example\Books\V1\EventBus\Publisher\Publisher as BooksV1Publisher;
use Example\Books\V1\EventBus\Receiver\BookCreatedEventHandlerInterface;
use Example\Books\V1\EventBus\Receiver\Receiver as BooksV1Receiver;
use Quarks\EventBus\Dispatcher\Adapter\SymfonyEventDispatcherAdapter;
use Quarks\EventBus\Dispatcher\Dispatcher;
use Quarks\EventBus\Encoding\ProtoJsonEncoder;
use Quarks\EventBus\Publisher;
use Quarks\EventBus\Receiver;
use Quarks\EventBus\Transport\ArrayTransport;
use Symfony\Component\EventDispatcher\EventDispatcher;

$amqpTransport = new ArrayTransport();
$publisher = new Publisher($amqpTransport, new ProtoJsonEncoder());

$booksV1Publisher = new BooksV1Publisher($publisher);
$booksV1Publisher->publishBookCreatedEvent(
    (new BookCreatedEvent())
        ->setId(312)
);

$eventDispatcher = new EventDispatcher();
$dispatcher = new Dispatcher(new SymfonyEventDispatcherAdapter($eventDispatcher));

$receiver = new Receiver($amqpTransport, new ProtoJsonEncoder(), $dispatcher);

$booksV1Receiver = new BooksV1Receiver($receiver, $dispatcher);
$booksV1Receiver->registerBookCreatedEventHandler(
    new class() implements BookCreatedEventHandlerInterface {
        public function handleBookCreatedEvent(BookCreatedEvent $event)
        {
            var_dump($event->getId());
        }
    });

pcntl_async_signals(true);
pcntl_signal(SIGINT, [$receiver, 'stop']);

$receiver->run();
