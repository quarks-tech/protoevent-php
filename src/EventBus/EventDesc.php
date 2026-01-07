<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus;

/**
 * Descriptor for a single event type
 */
final class EventDesc
{
    /**
     * @param string $name Event name (e.g., "BookCreated")
     * @param class-string $messageClass Full class name of the protobuf message
     * @param string $handlerMethod Method name to call on handler (e.g., "handleBookCreatedEvent")
     * @param class-string $handlerInterface Interface that handler must implement
     */
    public function __construct(
        private string $name,
        private string $messageClass,
        private string $handlerMethod,
        private string $handlerInterface,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return class-string
     */
    public function getMessageClass(): string
    {
        return $this->messageClass;
    }

    public function getHandlerMethod(): string
    {
        return $this->handlerMethod;
    }

    /**
     * @return class-string
     */
    public function getHandlerInterface(): string
    {
        return $this->handlerInterface;
    }
}
