<?php

namespace Quarks\EventBus\Descriptor;

interface ServiceDescriptorInterface
{
    public function getName(): string;
    public function findEventByName(string $eventName): EventDescriptor;
}
