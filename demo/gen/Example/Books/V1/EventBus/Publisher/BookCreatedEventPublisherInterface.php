<?php

namespace Example\Books\V1\EventBus\Publisher;

interface BookCreatedEventPublisherInterface
{
    public function publishBookCreatedEvent(\Example\Books\V1\BookCreatedEvent $event);
}

