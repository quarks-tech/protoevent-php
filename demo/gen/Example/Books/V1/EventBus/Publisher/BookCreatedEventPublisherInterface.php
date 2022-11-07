<?php

namespace Example\Books\V1\EventBus\Publisher;

use Example\Books\V1\BookCreatedEvent;

interface BookCreatedEventPublisherInterface
{
    public function publishBookCreatedEvent(BookCreatedEvent $event, array $options = []);
}
