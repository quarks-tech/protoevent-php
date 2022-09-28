<?php

namespace Quarks\EventBus\Transport;

use Quarks\EventBus\Message;

interface TransportInterface
{
    public function publish(string $eventName, $body, array $options = []);
    /** @return array<Message> */
    public function get(): iterable;
    public function ack(Message $message): void;
    public function reject(Message $message, bool $requeue = false): void;
    public function setup(): void;
}
