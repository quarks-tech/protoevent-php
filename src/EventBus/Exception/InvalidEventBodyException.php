<?php

namespace Quarks\EventBus\Exception;

class InvalidEventBodyException extends ReceiverException
{
    public function __construct(string $receivedClassName)
    {
        parent::__construct(
            sprintf('Expected instance of \Google\Protobuf\Internal\Message while received %s', $receivedClassName),
        );
    }
}
