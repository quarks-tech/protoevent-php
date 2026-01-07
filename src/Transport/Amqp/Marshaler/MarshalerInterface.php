<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport\Amqp\Marshaler;

use AMQPEnvelope;
use QuarksTech\ProtoEvent\Event\Metadata;

/**
 * Interface for marshaling/unmarshaling messages to/from AMQP format
 */
interface MarshalerInterface
{
    /**
     * Marshal metadata and data to AMQP message format
     *
     * @return array{body: string, attributes: array<string, mixed>}
     */
    public function marshal(Metadata $metadata, string $data): array;

    /**
     * Unmarshal AMQP envelope to metadata and data
     *
     * @return array{metadata: Metadata, data: string}
     */
    public function unmarshal(AMQPEnvelope $envelope): array;
}
