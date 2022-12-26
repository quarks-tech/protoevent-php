<?php

namespace Example\Books\V1\EventBus\Receiver;

interface BookCreatedEventHandlerInterface
{
    public function handleBookCreatedEvent(\Example\Books\V1\BookCreatedEvent $event);
}

