<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus\PublishOption;

use QuarksTech\ProtoEvent\Event\Metadata;
use QuarksTech\ProtoEvent\EventBus\PublishOption;

final class WithExtension implements PublishOption
{
    public function __construct(
        private string $name,
        private mixed $value,
    ) {}

    public function apply(Metadata $metadata): Metadata
    {
        return $metadata->withExtension($this->name, $this->value);
    }
}
