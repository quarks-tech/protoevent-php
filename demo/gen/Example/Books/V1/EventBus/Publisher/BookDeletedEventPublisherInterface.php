<?php

namespace Example\Books\V1\EventBus\Publisher;

use Example\Books\V1\BookDeletedEvent;

interface BookDeletedEventPublisherInterface
{
    public function publishBookDeletedEvent(BookDeletedEvent $event, array $options = []);
}
