<?php

namespace Example\Books\V1\EventBus;

use Quarks\EventBus\Descriptor\EventDescriptor;
use Quarks\EventBus\Exception\UnknownEventException;

class ServiceDescriptor
{
    public array $events = [
        
    ];

    public function __construct()
    {
        $this->events = [
        	'BookCreatedEvent' => new EventDescriptor('example.books.v1.BookCreated', \Example\Books\V1\BookCreatedEvent::class),
        	'BookUpdatedEvent' => new EventDescriptor('example.books.v1.BookUpdated', \Example\Books\V1\BookUpdatedEvent::class),
        	'BookDeletedEvent' => new EventDescriptor('example.books.v1.BookDeleted', \Example\Books\V1\BookDeletedEvent::class),
        ];
    }

    public static function create()
    {
        return new self();
    }

    public function getName() : string
    {
        return 'example.books.v1';
    }

    public function findEventByName(string $eventName) : \Quarks\EventBus\Descriptor\EventDescriptor
    {
        if (!isset($this->events[$eventName])) {
            throw new UnknownEventException($eventName);
        }

        return $this->events[$eventName];
    }
}

