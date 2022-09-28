<?php

namespace Quarks\EventBus\Exception;

class UnknownEventException extends \Exception
{
    public function __construct(string $eventName)
    {
        parent::__construct(
            sprintf('Trying to process unknown event (%s) via EventBus', $eventName),
        );
    }
}
