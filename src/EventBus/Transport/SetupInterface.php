<?php

namespace Quarks\EventBus\Transport;

interface SetupInterface
{
    public function setup(array $registeredEvents): void;
}
