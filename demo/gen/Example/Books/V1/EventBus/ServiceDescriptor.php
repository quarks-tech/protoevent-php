<?php

namespace Example\Books\V1\EventBus;

use Example\Books\V1\BookCreatedEvent;
use Example\Books\V1\BookDeletedEvent;
use Example\Books\V1\BookUpdatedEvent;
use Quarks\EventBus\Descriptor\EventDescriptor;
use Quarks\EventBus\Exception\UnknownEventException;

class ServiceDescriptor
{
    /**
     * @var array<EventDescriptor>
     */
    private array $eventsMap = [];

    public function __construct()
    {
        $this->eventsMap = [
            'BookCreatedEvent' => new EventDescriptor('example.books.v1.BookCreated', BookCreatedEvent::class),
            'BookDeletedEvent' => new EventDescriptor('example.books.v1.BookDeleted', BookDeletedEvent::class),
            'BookUpdatedEvent' => new EventDescriptor('example.books.v1.BookUpdated', BookUpdatedEvent::class),
        ];
    }

    public static function create(): self
    {
        return new ServiceDescriptor();
    }

    public function getName(): string
    {
        return 'example.books.v1';
    }

    /**
     * @throws UnknownEventException
     */
    public function findEventByName(string $eventName): EventDescriptor
    {
        if (!isset($this->eventsMap[$eventName])) {
            throw new UnknownEventException($eventName);
        }

        return $this->eventsMap[$eventName];
    }
}
