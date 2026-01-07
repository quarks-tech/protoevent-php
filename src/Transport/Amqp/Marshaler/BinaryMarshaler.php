<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport\Amqp\Marshaler;

use AMQPEnvelope;
use DateTimeImmutable;
use DateTimeInterface;
use QuarksTech\ProtoEvent\Event\Metadata;
use QuarksTech\ProtoEvent\Exception\UnprocessableEventException;
use Throwable;

/**
 * Binary content mode: CloudEvents attributes in AMQP headers, payload in body
 */
final class BinaryMarshaler implements MarshalerInterface
{
    private const CE_PREFIX = 'cloudEvents:';

    public function marshal(Metadata $metadata, string $data): array
    {
        $headers = [
            self::CE_PREFIX . 'specversion' => $metadata->getSpecVersion(),
            self::CE_PREFIX . 'id' => $metadata->getId(),
            self::CE_PREFIX . 'source' => $metadata->getSource(),
            // Use RFC3339 (no fractional seconds) for Go interop (time.RFC3339)
            self::CE_PREFIX . 'time' => $metadata->getTime()->format(DateTimeInterface::RFC3339),
        ];

        if ($metadata->getSubject() !== null) {
            $headers[self::CE_PREFIX . 'subject'] = $metadata->getSubject();
        }

        if ($metadata->getDataSchema() !== null) {
            $headers[self::CE_PREFIX . 'dataschema'] = $metadata->getDataSchema();
        }

        // Add extensions without prefix
        foreach ($metadata->getExtensions() as $key => $value) {
            $headers[$key] = $value;
        }

        return [
            'body' => $data,
            'attributes' => [
                'headers' => $headers,
                'content_type' => $metadata->getDataContentType(),
                'type' => $metadata->getType(),
                'timestamp' => $metadata->getTime()->getTimestamp(),
                'app_id' => 'protoevent-php',
            ],
        ];
    }

    public function unmarshal(AMQPEnvelope $envelope): array
    {
        /** @var array<string, mixed> $headers */
        $headers = $envelope->getHeaders() ?? [];

        $type = $envelope->getType();
        if (empty($type)) {
            throw new UnprocessableEventException("Required attribute 'type' is missing");
        }

        $specVersion = $this->getHeader($headers, 'specversion') ?? '1.0';
        $id = $this->getHeader($headers, 'id');
        $source = $this->getHeader($headers, 'source') ?? $envelope->getAppId() ?? 'unknown';

        if ($id === null) {
            throw new UnprocessableEventException("Required attribute 'id' is missing");
        }

        // Parse time
        $timeStr = $this->getHeader($headers, 'time');
        if ($timeStr !== null) {
            try {
                $time = new DateTimeImmutable($timeStr);
            } catch (Throwable $e) {
                throw new UnprocessableEventException("Failed to parse attribute 'time': " . $e->getMessage(), previous: $e);
            }
        } else {
            $timestamp = $envelope->getTimestamp();
            $time = $timestamp > 0
                ? (new DateTimeImmutable())->setTimestamp($timestamp)
                : new DateTimeImmutable();
        }

        // Extract extensions (non-cloudEvents headers)
        $extensions = [];
        foreach ($headers as $key => $value) {
            if (!str_starts_with($key, self::CE_PREFIX) && $key !== 'x-death' && $key !== 'x-first-death-queue' && $key !== 'x-first-death-reason') {
                $extensions[$key] = $value;
            }
        }

        $metadata = new Metadata(
            id: $id,
            type: $type,
            source: $source,
            time: $time,
            specVersion: $specVersion,
            dataContentType: $envelope->getContentType() ?: 'application/proto',
            subject: $this->getHeader($headers, 'subject'),
            dataSchema: $this->getHeader($headers, 'dataschema'),
            extensions: $extensions,
        );

        return [
            'metadata' => $metadata,
            'data' => $envelope->getBody(),
        ];
    }

    /**
     * @param array<string, mixed> $headers
     */
    private function getHeader(array $headers, string $name): ?string
    {
        $key = self::CE_PREFIX . $name;
        return isset($headers[$key]) ? (string) $headers[$key] : null;
    }
}
