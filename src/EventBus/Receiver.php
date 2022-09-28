<?php

namespace Quarks\EventBus;

use Google\Protobuf\Internal\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Quarks\EventBus\Descriptor\EventDescriptor;
use Quarks\EventBus\Dispatcher\Dispatcher;
use Quarks\EventBus\Encoding\DecoderInterface;
use Quarks\EventBus\Exception\InvalidEventBodyException;
use Quarks\EventBus\Exception\MessageDecodingFailedException;
use Quarks\EventBus\Exception\ReceiverException;
use Quarks\EventBus\Transport\TransportInterface;

class Receiver
{
    private const SLEEP = 1000000; // time in microseconds to sleep after no messages are found

    private TransportInterface $transport;
    private DecoderInterface $decoder;
    private Dispatcher $dispatcher;
    private LoggerInterface $logger;

    private array $registeredEventsMap = [];

    private bool $transportSetup = false;
    private bool $shouldStop = false;

    public function __construct(TransportInterface $transport, DecoderInterface $decoder, Dispatcher $dispatcher)
    {
        $this->transport = $transport;
        $this->decoder = $decoder;
        $this->dispatcher = $dispatcher;
        $this->logger = new NullLogger();
    }

    public function run(): void
    {
        if (!$this->transportSetup) {
            $this->setupTransport();
        }

        while (false === $this->shouldStop) {
            $messageReceived = false;

            foreach ($this->transport->get() as $message) {
                try {
                    $messageReceived = true;

                    $this->dispatchEvent($message);
                    $this->transport->ack($message);

                    if ($this->shouldStop) {
                        $this->logger->info("Stopping receiver...");

                        break;
                    }
                } catch (MessageDecodingFailedException $e) {
                    $this->logger->error('Unable to decode message from transport', [
                        'transport' => get_class($this->transport)
                    ]);

                    $this->transport->reject($message);
                } catch (ReceiverException $e) {
                    $this->logger->error(sprintf("Unable to process event: %s", $e->getMessage()));

                    $this->transport->reject($message, true);
                }
            }

            if (false === $messageReceived) {
                usleep(self::SLEEP);
            }
        }
    }

    public function stop(): void
    {
        $this->shouldStop = true;
    }

    public function register(EventDescriptor $eventDescriptor): void
    {
        if (!empty($this->registeredEventsMap[$eventDescriptor->getFullName()])) {
            return;
        }

        $this->registeredEventsMap[$eventDescriptor->getFullName()] = $eventDescriptor->getClass();
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @throws ReceiverException
     */
    private function dispatchEvent(\Quarks\EventBus\Message $message): void
    {
        try {
            $cloudEvent = $this->decoder->decode($message->getBody());
            $eventName = $cloudEvent->getType();

            // dispatch only events we subscribed
            if (empty($eventClass = $this->registeredEventsMap[$eventName] ?? null)) {
                return;
            }

            if (!class_exists($eventClass)) {
                throw new \LogicException(sprintf("Class %s should be exists", $eventClass));
            }

            $event = new $eventClass;

            if (!$event instanceof Message) {
                throw new InvalidEventBodyException($eventClass);
            }

            $event->mergeFromJsonString($cloudEvent->getData());

            $this->dispatcher->dispatch($event, $eventName);
        } catch (\Exception $e) {
            throw new ReceiverException('', 0, $e);
        }
    }

    private function setupTransport(): void
    {
        if ($this->transportSetup) {
            return;
        }

        $this->transport->setup();
        $this->transportSetup = true;
    }
}
