<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Event;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * CloudEvents 1.0 compliant event metadata
 *
 * @see https://github.com/cloudevents/spec/blob/v1.0.2/cloudevents/spec.md
 */
final class Metadata
{
    /**
     * @param array<string, mixed> $extensions
     */
    public function __construct(
        private string $id,
        private string $type,
        private string $source,
        private DateTimeImmutable $time,
        private string $specVersion = '1.0',
        private string $dataContentType = 'application/proto',
        private ?string $subject = null,
        private ?string $dataSchema = null,
        private array $extensions = [],
    ) {}

    public static function create(string $type, string $source = 'protoevent-php'): self
    {
        return new self(
            id: Uuid::uuid4()->toString(),
            type: $type,
            source: $source,
            time: new DateTimeImmutable(),
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTime(): DateTimeImmutable
    {
        return $this->time;
    }

    public function getSpecVersion(): string
    {
        return $this->specVersion;
    }

    public function getDataContentType(): string
    {
        return $this->dataContentType;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getDataSchema(): ?string
    {
        return $this->dataSchema;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function getExtension(string $name): mixed
    {
        return $this->extensions[$name] ?? null;
    }

    public function withExtension(string $name, mixed $value): self
    {
        $clone = clone $this;
        $clone->extensions[$name] = $value;
        return $clone;
    }

    public function withSubject(string $subject): self
    {
        $clone = clone $this;
        $clone->subject = $subject;
        return $clone;
    }

    public function withSource(string $source): self
    {
        $clone = clone $this;
        $clone->source = $source;
        return $clone;
    }

    public function withDataContentType(string $contentType): self
    {
        $clone = clone $this;
        $clone->dataContentType = $contentType;
        return $clone;
    }

    public function withDataSchema(string $dataSchema): self
    {
        $clone = clone $this;
        $clone->dataSchema = $dataSchema;
        return $clone;
    }

    /**
     * Extract service name from type (everything before last dot)
     * Example: "example.books.v1.BookCreated" -> "example.books.v1"
     */
    public function getServiceName(): string
    {
        $pos = strrpos($this->type, '.');
        return $pos !== false ? substr($this->type, 0, $pos) : '';
    }

    /**
     * Extract event name from type (everything after last dot)
     * Example: "example.books.v1.BookCreated" -> "BookCreated"
     */
    public function getEventName(): string
    {
        $pos = strrpos($this->type, '.');
        return $pos !== false ? substr($this->type, $pos + 1) : $this->type;
    }
}
