<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus;

use Google\Protobuf\Internal\Message;
use QuarksTech\ProtoEvent\Event\Metadata;

/**
 * Interceptor for publisher operations
 */
interface PublisherInterceptor
{
    /**
     * Intercept a publish operation
     *
     * @param callable(Metadata, Message): void $next
     */
    public function intercept(Metadata $metadata, Message $event, callable $next): void;
}
