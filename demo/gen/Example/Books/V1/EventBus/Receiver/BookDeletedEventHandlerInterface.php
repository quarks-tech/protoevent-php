<?php

namespace Example\Books\V1\EventBus\Receiver;

interface BookDeletedEventHandlerInterface
{
    public function handleBookDeletedEvent(\Example\Books\V1\BookDeletedEvent $event);
}

