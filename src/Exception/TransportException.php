<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Exception;

/**
 * Thrown when a transport-level error occurs (connection issues, publish failures, etc.)
 */
class TransportException extends ProtoEventException {}
