<?php

namespace Example\Books\V1\EventBus\Receiver;

interface BookUpdatedEventHandlerInterface
{
    public function handleBookUpdatedEvent(\Example\Books\V1\BookUpdatedEvent $event);
}

