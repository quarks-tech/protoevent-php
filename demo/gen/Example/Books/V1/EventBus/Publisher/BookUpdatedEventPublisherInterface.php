<?php

namespace Example\Books\V1\EventBus\Publisher;

interface BookUpdatedEventPublisherInterface
{
    public function publishBookUpdatedEvent(\Example\Books\V1\BookUpdatedEvent $event);
}

