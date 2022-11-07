<?php

require_once "../vendor/autoload.php";

use Example\Books\V1\BookCreatedEvent;
use Example\Books\V1\EventBus\Receiver\BookCreatedEventHandlerInterface;
use Quarks\EventBus\Dispatcher\Adapter\SymfonyEventDispatcherAdapter;
use Quarks\EventBus\Dispatcher\Dispatcher;
use Quarks\EventBus\Encoding\ProtoJsonEncoder;
use Quarks\EventBus\Receiver as BaseReceiver;
use Quarks\EventBus\Transport\ArrayTransport;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Example\Books\V1\EventBus\Receiver\Receiver as BooksV1Receiver;

$eventDispatcher = new EventDispatcher();

$dispatcher = new Dispatcher(new SymfonyEventDispatcherAdapter($eventDispatcher));
$amqpTransport = new ArrayTransport();

$receiver = new BaseReceiver($amqpTransport, new ProtoJsonEncoder(), $dispatcher);

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
