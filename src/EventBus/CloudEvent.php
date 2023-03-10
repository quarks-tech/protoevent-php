<?php

namespace Quarks\EventBus;

class CloudEvent
{
    private const SPEC_VERSION = '1.0';

    private string $id;
    private string $source;
    private string $type;
    private array $data;
    private string $dataContentType;
    private \DateTimeImmutable $time;

    public function __construct(
        string $id,
        string $source,
        string $type,
        array $data,
        ?string $dataContentType = null,
        ?\DateTimeImmutable $time = null,
    ) {
        $this->id = $id;
        $this->source = $source;
        $this->type = $type;
        $this->data = $data;
        $this->dataContentType = $dataContentType;
        $this->time = $time;
    }

    public function getSpecVersion(): string
    {
        return self::SPEC_VERSION;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getDataContentType(): ?string
    {
        return $this->dataContentType;
    }

    public function getTime(): ?\DateTimeImmutable
    {
        return $this->time;
    }
}
