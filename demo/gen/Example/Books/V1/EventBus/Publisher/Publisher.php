<?php

namespace Example\Books\V1\EventBus\Publisher;

class Publisher implements BookCreatedEventPublisherInterface, BookUpdatedEventPublisherInterface, BookDeletedEventPublisherInterface
{
    public \Quarks\EventBus\Publisher $publisher;

    public function __construct(\Quarks\EventBus\Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function publishBookCreatedEvent(\Example\Books\V1\BookCreatedEvent $event, array $options = [])
    {
        $this->publisher->publish($event, 'example.books.v1.BookCreated', $options);
    }

    public function publishBookUpdatedEvent(\Example\Books\V1\BookUpdatedEvent $event, array $options = [])
    {
        $this->publisher->publish($event, 'example.books.v1.BookUpdated', $options);
    }

    public function publishBookDeletedEvent(\Example\Books\V1\BookDeletedEvent $event, array $options = [])
    {
        $this->publisher->publish($event, 'example.books.v1.BookDeleted', $options);
    }
}

