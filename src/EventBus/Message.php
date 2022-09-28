<?php

namespace Quarks\EventBus;

class Message
{
    private $body;
    private array $markers = [];

    public function __construct($body, array $markers)
    {
        $this->body = $body;
        $this->markers = $markers;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getMarkers(): array
    {
        return $this->markers;
    }

    public function getMarker(string $name): string
    {
        return $this->markers[$name] ?? '';
    }
}
