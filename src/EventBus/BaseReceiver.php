<?php

namespace Quarks\EventBus;

use Google\Protobuf\Internal\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Quarks\EventBus\Descriptor\EventDescriptor;
use Quarks\EventBus\Dispatcher\Dispatcher;
use Quarks\EventBus\Encoding\Codecs\JsonCodec;
use Quarks\EventBus\Encoding\Codecs\ProtoCodec;
use Quarks\EventBus\Exception\InvalidEventBodyException;
use Quarks\EventBus\Exception\ReceiverException;
use Quarks\EventBus\Exception\UnsupportedContentType;

abstract class BaseReceiver
{
    protected Dispatcher $dispatcher;
    protected LoggerInterface $logger;

    protected array $registeredEvents = [];
    protected bool $transportSetup = false;
    protected bool $shouldStop = false;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = new NullLogger();
    }

    public function register(EventDescriptor $eventDescriptor): void
    {
        if (!empty($this->registeredEvents[$eventDescriptor->getFullName()])) {
            return;
        }

        $this->registeredEvents[$eventDescriptor->getFullName()] = $eventDescriptor->getClass();
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function stop(): void
    {
        $this->shouldStop = true;
    }

    /**
     * @throws ReceiverException
     */
    protected function dispatchEvent(Envelope $envelope): void
    {
        try {
            $eventName = $envelope->getMetadata()->getType();

            // dispatch only events we subscribed
            if (empty($eventClass = $this->registeredEvents[$eventName] ?? null)) {
                return;
            }

            if (!class_exists($eventClass)) {
                throw new \LogicException(sprintf("Class %s should be exists", $eventClass));
            }

            $event = new $eventClass;

            if (!$event instanceof Message) {
                throw new InvalidEventBodyException($eventClass);
            }

            CodecsHelper::decodeWithCodec(
                $event,
                $envelope->getBody(),
                ContentTypeHelper::extractSubType($envelope->getMetadata()->getDataContentType())
            );

            $this->dispatcher->dispatch($event, $eventName);
        } catch (\Exception $e) {
            throw new ReceiverException($e->getMessage(), 0, $e);
        }
    }
}
