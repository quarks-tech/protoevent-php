<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport\Amqp\Marshaler;

use AMQPEnvelope;
use QuarksTech\ProtoEvent\Event\Metadata;

/**
 * Auto-selects between binary and structured mode based on content type
 */
final class Marshaler implements MarshalerInterface
{
    private const STRUCTURED_CONTENT_TYPE = 'application/cloudevents+json';

    private BinaryMarshaler $binaryMarshaler;
    private StructuredMarshaler $structuredMarshaler;

    public function __construct()
    {
        $this->binaryMarshaler = new BinaryMarshaler();
        $this->structuredMarshaler = new StructuredMarshaler();
    }

    public function marshal(Metadata $metadata, string $data): array
    {
        if ($this->normalizeContentType($metadata->getDataContentType()) === self::STRUCTURED_CONTENT_TYPE) {
            return $this->structuredMarshaler->marshal($metadata, $data);
        }

        return $this->binaryMarshaler->marshal($metadata, $data);
    }

    public function unmarshal(AMQPEnvelope $envelope): array
    {
        if ($this->normalizeContentType($envelope->getContentType()) === self::STRUCTURED_CONTENT_TYPE) {
            return $this->structuredMarshaler->unmarshal($envelope);
        }

        return $this->binaryMarshaler->unmarshal($envelope);
    }

    private function normalizeContentType(?string $contentType): string
    {
        if ($contentType === null) {
            return '';
        }

        $contentType = strtolower(trim($contentType));
        $semi = strpos($contentType, ';');
        if ($semi !== false) {
            $contentType = trim(substr($contentType, 0, $semi));
        }

        return $contentType;
    }
}
