<?php

namespace Quarks\EventBus;

use Quarks\EventBus\Descriptor\EventDescriptor;
use Quarks\EventBus\Dispatcher\Dispatcher;
use Quarks\EventBus\Exception\MessageDecodingFailedException;
use Quarks\EventBus\Exception\ReceiverException;
use Quarks\EventBus\Transport\BlockingTransportInterface;

class BlockingReceiver extends BaseReceiver
{
    private BlockingTransportInterface $transport;

    public function __construct(BlockingTransportInterface $transport, Dispatcher $dispatcher)
    {
        $this->transport = $transport;

        parent::__construct($dispatcher);
    }

    public function run(): void
    {
        if (!$this->transportSetup) {
            $this->setupTransport();
        }

        $this->transport->fetch(function (Envelope $message) {
            try {
                $this->dispatchEvent($message);
                $this->transport->ack($message);

                if ($this->shouldStop) {
                    $this->logger->info("Stopping receiver...");

                    return;
                }
            } catch (MessageDecodingFailedException) {
                $this->logger->error('Unable to decode message from transport', [
                    'transport' => get_class($this->transport)
                ]);

                $this->transport->reject($message);
            } catch (ReceiverException $e) {
                $this->logger->error(sprintf("Unable to process event: %s", $e->getMessage()));

                $this->transport->reject($message, true);
            } catch (\Throwable $throwable) {
                $this->logger->error(sprintf("Unable to process event: %s", $throwable->getMessage()));

                $this->transport->reject($message);
            }
        });
    }

    public function register(EventDescriptor $eventDescriptor): void
    {
        if (!empty($this->registeredEvents[$eventDescriptor->getFullName()])) {
            return;
        }

        $this->registeredEvents[$eventDescriptor->getFullName()] = $eventDescriptor->getClass();
    }

    private function setupTransport(): void
    {
        if ($this->transportSetup) {
            return;
        }

        $this->transport->setup($this->registeredEvents);
        $this->transportSetup = true;
    }
}
