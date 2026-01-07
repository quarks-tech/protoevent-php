<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Exception;

/**
 * Thrown when an event cannot be processed and should not be retried.
 *
 * When this exception is thrown, the message will be rejected without requeue
 * and sent directly to parking lot (if configured) or dead letter queue.
 */
class UnprocessableEventException extends ProtoEventException {}
