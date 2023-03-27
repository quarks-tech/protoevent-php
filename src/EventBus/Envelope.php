<?php

namespace Quarks\EventBus;

class Envelope
{
    private Metadata $metadata;
    private mixed $body;
    private array $markers = [];

    public function __construct(Metadata $metadata, mixed $body, array $markers = [])
    {
        $this->metadata = $metadata;
        $this->body = $body;
        $this->markers = $markers;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }

    public function getMarkers(): array
    {
        return $this->markers;
    }

    public function addMarker(string $name, string $value): self
    {
        $this->markers[$name] = $value;

        return $this;
    }

    public function getMarker(string $name)
    {
        return $this->markers[$name] ?? '';
    }
}
