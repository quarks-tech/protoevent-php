<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus\PublishOption;

use QuarksTech\ProtoEvent\Event\Metadata;
use QuarksTech\ProtoEvent\EventBus\PublishOption;

final class WithDataSchema implements PublishOption
{
    public function __construct(
        private string $dataSchema,
    ) {}

    public function apply(Metadata $metadata): Metadata
    {
        return $metadata->withDataSchema($this->dataSchema);
    }
}
