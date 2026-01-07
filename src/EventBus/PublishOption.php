<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus;

use QuarksTech\ProtoEvent\Event\Metadata;

interface PublishOption
{
    public function apply(Metadata $metadata): Metadata;
}
