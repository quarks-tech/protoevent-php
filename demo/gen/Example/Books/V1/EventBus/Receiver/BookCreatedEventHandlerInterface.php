<?php

namespace Example\Books\V1\EventBus\Receiver;

use Example\Books\V1\BookCreatedEvent;

interface BookCreatedEventHandlerInterface
{
    public function handleBookCreatedEvent(BookCreatedEvent $event);
}
