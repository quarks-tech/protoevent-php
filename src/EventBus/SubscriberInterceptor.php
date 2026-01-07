<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus;

use Google\Protobuf\Internal\Message;

/**
 * Interceptor for subscriber operations
 */
interface SubscriberInterceptor
{
    /**
     * Intercept a subscribe operation
     *
     * @param callable(EventContext, Message): void $next
     */
    public function intercept(EventContext $context, Message $event, callable $next): void;
}
