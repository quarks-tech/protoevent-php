<?php

namespace Example\Books\V1\EventBus\Publisher;

use Example\Books\V1\BookCreatedEvent;
use Example\Books\V1\BookDeletedEvent;
use Example\Books\V1\BookUpdatedEvent;
use Quarks\EventBus\Exception\PublisherException;
use Quarks\EventBus\Publisher as BasePublisher;

class Publisher implements BookCreatedEventPublisherInterface, BookDeletedEventPublisherInterface, BookUpdatedEventPublisherInterface
{
    private BasePublisher $publisher;

    public function __construct(BasePublisher $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * @throws PublisherException
     */
    public function publishBookCreatedEvent(BookCreatedEvent $event, array $options = [])
    {
        $this->publisher->publish($event, 'example.books.v1.BookCreated', $options);
    }

    /**
     * @throws PublisherException
     */
    public function publishBookDeletedEvent(BookDeletedEvent $event, array $options = [])
    {
        $this->publisher->publish($event, 'example.books.v1.BookDeleted', $options);
    }

    /**
     * @throws PublisherException
     */
    public function publishBookUpdatedEvent(BookUpdatedEvent $event, array $options = [])
    {
        $this->publisher->publish($event, 'example.books.v1.BookUpdated', $options);
    }
}
