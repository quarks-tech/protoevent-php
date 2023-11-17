<?php

namespace Example\Books\V1\EventBus\Receiver;

class Receiver
{
    public \Quarks\EventBus\BaseReceiver $receiver;

    public \Example\Books\V1\EventBus\ServiceDescriptor $serviceDescriptor;

    public \Quarks\EventBus\Dispatcher\Dispatcher $dispatcher;

    public function __construct(\Quarks\EventBus\BaseReceiver $receiver, \Quarks\EventBus\Dispatcher\Dispatcher $dispatcher)
    {
        $this->receiver = $receiver;
        $this->serviceDescriptor = \Example\Books\V1\EventBus\ServiceDescriptor::create();
        $this->dispatcher = $dispatcher;
    }

    public function registerBookCreatedEventHandler(\Example\Books\V1\EventBus\Receiver\BookCreatedEventHandlerInterface $handler)
    {
        $event = $this->serviceDescriptor->findEventByName('BookCreatedEvent');

        $this->receiver->register($event);
        $this->dispatcher->registerHandler($event->getFullName(), [$handler, 'handleBookCreatedEvent']);
    }

    public function registerBookUpdatedEventHandler(\Example\Books\V1\EventBus\Receiver\BookUpdatedEventHandlerInterface $handler)
    {
        $event = $this->serviceDescriptor->findEventByName('BookUpdatedEvent');

        $this->receiver->register($event);
        $this->dispatcher->registerHandler($event->getFullName(), [$handler, 'handleBookUpdatedEvent']);
    }

    public function registerBookDeletedEventHandler(\Example\Books\V1\EventBus\Receiver\BookDeletedEventHandlerInterface $handler)
    {
        $event = $this->serviceDescriptor->findEventByName('BookDeletedEvent');

        $this->receiver->register($event);
        $this->dispatcher->registerHandler($event->getFullName(), [$handler, 'handleBookDeletedEvent']);
    }
}

