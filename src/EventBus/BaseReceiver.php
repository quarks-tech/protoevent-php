<?php

namespace Quarks\EventBus;

use Google\ApiCore\Serializer;
use Google\Protobuf\Internal\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Quarks\EventBus\Descriptor\EventDescriptor;
use Quarks\EventBus\Dispatcher\Dispatcher;
use Quarks\EventBus\Encoding\DecoderInterface;
use Quarks\EventBus\Exception\InvalidEventBodyException;
use Quarks\EventBus\Exception\ReceiverException;

abstract class BaseReceiver
{
    protected DecoderInterface $decoder;
    protected Dispatcher $dispatcher;
    protected Serializer $serializer;
    protected LoggerInterface $logger;

    protected array $registeredEvents = [];
    protected bool $transportSetup = false;
    protected bool $shouldStop = false;

    public function __construct(DecoderInterface $decoder, Dispatcher $dispatcher)
    {
        $this->decoder = $decoder;
        $this->dispatcher = $dispatcher;
        $this->serializer = new Serializer();
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
    protected function dispatchEvent(\Quarks\EventBus\Message $message): void
    {
        try {
            $cloudEvent = $this->decoder->decode($message->getBody());
            $eventName = $cloudEvent->getType();

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

            $this->serializer->decodeMessage($eventClass, $cloudEvent->getData());

            $this->dispatcher->dispatch($event, $eventName);
        } catch (\Exception $e) {
            throw new ReceiverException('', 0, $e);
        }
    }
}
