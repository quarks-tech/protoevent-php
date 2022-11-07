<?php

namespace Quarks\EventBus\Transport;

use Quarks\EventBus\Transport\TransportInterface;

interface TransportFactoryInterface
{
    public static function createTransport(string $dsn, array $options): TransportInterface;
}
