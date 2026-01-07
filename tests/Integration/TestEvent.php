<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Tests\Integration;

use Google\Protobuf\Internal\Message;

/**
 * Simple test event message (mock protobuf message)
 */
class TestEvent extends Message
{
    private string $id = '';
    private string $name = '';

    public function __construct($data = null)
    {
        // Skip parent constructor to avoid protobuf descriptor requirements
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function serializeToString(): string
    {
        return json_encode(['id' => $this->id, 'name' => $this->name]);
    }

    public function mergeFromString($data): void
    {
        $decoded = json_decode($data, true);
        $this->id = $decoded['id'] ?? '';
        $this->name = $decoded['name'] ?? '';
    }
}
