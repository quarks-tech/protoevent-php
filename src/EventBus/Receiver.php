<?php

namespace Quarks\EventBus;

use Quarks\EventBus\Dispatcher\Dispatcher;
use Quarks\EventBus\Encoding\DecoderInterface;
use Quarks\EventBus\Exception\MessageDecodingFailedException;
use Quarks\EventBus\Exception\ReceiverException;
use Quarks\EventBus\Transport\TransportInterface;

class Receiver extends BaseReceiver
{
    private const SLEEP = 1000000; // time in microseconds to sleep after no messages are found

    private TransportInterface $transport;

    public function __construct(TransportInterface $transport, DecoderInterface $decoder, Dispatcher $dispatcher)
    {
        $this->transport = $transport;

        parent::__construct($decoder, $dispatcher);
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

    private function setupTransport(): void
    {
        if ($this->transportSetup) {
            return;
        }

        $this->transport->setup($this->registeredEvents);
        $this->transportSetup = true;
    }
}
