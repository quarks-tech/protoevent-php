<?php

namespace Quarks\EventBus\Descriptor;

class EventDescriptor
{
    public string $fullName;
    private string $class;

    public function __construct(string $fullName, string $class)
    {
        $this->fullName = $fullName;
        $this->class = $class;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getClass(): string
    {
        return $this->class;
    }
}
