<?php

namespace Example\Books\V1\EventBus\Publisher;

interface BookDeletedEventPublisherInterface
{
    public function publishBookDeletedEvent(\Example\Books\V1\BookDeletedEvent $event);
}

