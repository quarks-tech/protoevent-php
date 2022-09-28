<?php

namespace Example\Books\V1\EventBus\Receiver;

use Example\Books\V1\EventBus\ServiceDescriptor;
use Quarks\EventBus\Dispatcher\Dispatcher;
use Quarks\EventBus\Receiver as BaseReceiver;

class Receiver
{
    private BaseReceiver $receiver;
    private ServiceDescriptor $serviceDescription;
    private Dispatcher $dispatcher;

    public function __construct(BaseReceiver $receiver, Dispatcher $dispatcher)
    {
        $this->receiver = $receiver;
        $this->serviceDescription = ServiceDescriptor::create();
        $this->dispatcher = $dispatcher;
    }

    public function registerBookCreatedEventHandler(BookCreatedEventHandlerInterface $handler): void
    {
        $event = $this->serviceDescription->findEventByName('BookCreatedEvent');

        $this->receiver->register($event);
        $this->dispatcher->registerHandler($event->getFullName(), [$handler, 'handleBookCreatedEvent']);
    }
}
