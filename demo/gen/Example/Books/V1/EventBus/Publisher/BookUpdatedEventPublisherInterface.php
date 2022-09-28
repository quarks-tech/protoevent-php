<?php

namespace Example\Books\V1\EventBus\Publisher;

use Example\Books\V1\BookUpdatedEvent;

interface BookUpdatedEventPublisherInterface
{
    public function publishBookUpdatedEvent(BookUpdatedEvent $event, array $options = []);
}
