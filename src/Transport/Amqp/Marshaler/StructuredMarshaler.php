<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Transport\Amqp\Marshaler;

use AMQPEnvelope;
use DateTimeImmutable;
use DateTimeInterface;
use JsonException;
use QuarksTech\ProtoEvent\Event\Metadata;
use QuarksTech\ProtoEvent\Exception\UnprocessableEventException;

/**
 * Structured content mode: Full CloudEvents JSON in message body
 */
final class StructuredMarshaler implements MarshalerInterface
{
    private const CONTENT_TYPE = 'application/cloudevents+json';

    public function marshal(Metadata $metadata, string $data): array
    {
        $event = [
            'specversion' => $metadata->getSpecVersion(),
            'id' => $metadata->getId(),
            'type' => $metadata->getType(),
            'source' => $metadata->getSource(),
            // Use RFC3339 (no fractional seconds) for Go interop (time.RFC3339)
            'time' => $metadata->getTime()->format(DateTimeInterface::RFC3339),
        ];

        // Data must be JSON in protoevent structured mode (matches protoevent-amqp-go).
        try {
            $event['data'] = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new UnprocessableEventException('Structured mode requires JSON data: ' . $e->getMessage(), previous: $e);
        }

        // Always include datacontenttype in structured mode for protoevent-go subscriber compatibility
        // (it requires a non-empty md.DataContentType to pick a codec)
        if ($metadata->getDataContentType() !== '') {
            $event['datacontenttype'] = $metadata->getDataContentType();
        }

        if ($metadata->getSubject() !== null) {
            $event['subject'] = $metadata->getSubject();
        }

        if ($metadata->getDataSchema() !== null) {
            $event['dataschema'] = $metadata->getDataSchema();
        }

        foreach ($metadata->getExtensions() as $key => $value) {
            $event[$key] = $value;
        }

        try {
            $body = json_encode($event, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new UnprocessableEventException('Failed to encode CloudEvents JSON: ' . $e->getMessage(), previous: $e);
        }

        return [
            'body' => $body,
            'attributes' => [
                'content_type' => self::CONTENT_TYPE,
                'type' => $metadata->getType(),
                'timestamp' => $metadata->getTime()->getTimestamp(),
                'app_id' => 'protoevent-php',
            ],
        ];
    }

    public function unmarshal(AMQPEnvelope $envelope): array
    {
        try {
            $event = json_decode($envelope->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new UnprocessableEventException('Failed to decode CloudEvents JSON: ' . $e->getMessage(), previous: $e);
        }

        if (!is_array($event)) {
            throw new UnprocessableEventException('CloudEvents JSON must be an object');
        }

        $knownFields = [
            'specversion', 'type', 'source', 'id', 'time',
            'subject', 'dataschema', 'datacontenttype', 'data', 'data_base64',
        ];

        $extensions = [];
        foreach ($event as $key => $value) {
            if (!in_array($key, $knownFields, true)) {
                $extensions[$key] = $value;
            }
        }

        if (!isset($event['specversion'], $event['type'], $event['source'], $event['id'])) {
            throw new UnprocessableEventException('Missing required CloudEvents attributes');
        }

        if (isset($event['time'])) {
            try {
                $time = new DateTimeImmutable($event['time']);
            } catch (\Throwable $e) {
                throw new UnprocessableEventException("Failed to parse attribute 'time': " . $e->getMessage(), previous: $e);
            }
        } else {
            $time = new DateTimeImmutable();
        }

        $metadata = new Metadata(
            id: $event['id'],
            type: $event['type'],
            source: $event['source'],
            time: $time,
            specVersion: $event['specversion'],
            dataContentType: $event['datacontenttype'] ?? 'application/json',
            subject: $event['subject'] ?? null,
            dataSchema: $event['dataschema'] ?? null,
            extensions: $extensions,
        );

        // Extract data
        if (isset($event['data'])) {
            try {
                $data = json_encode($event['data'], JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT);
            } catch (JsonException $e) {
                throw new UnprocessableEventException('Failed to encode data: ' . $e->getMessage(), previous: $e);
            }
        } elseif (isset($event['data_base64'])) {
            $data = base64_decode($event['data_base64'], true);
            if ($data === false) {
                throw new UnprocessableEventException('Failed to decode base64 data');
            }
        } else {
            throw new UnprocessableEventException("Required attribute 'data' is missing");
        }

        return [
            'metadata' => $metadata,
            'data' => $data,
        ];
    }
}
