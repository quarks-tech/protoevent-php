<?php

namespace Quarks\EventBus;

class CloudEvent
{
    public const SPEC_VERSION = '1.0';

    public const JSON_CONTENT_TYPE = 'application/cloudevents+json';

    private string $id;
    private string $source;
    private string $type;
    private mixed $data;
    private string $dataContentType;
    private \DateTimeImmutable $time;

    public function __construct(
        string $id,
        string $source,
        string $type,
        mixed $data,
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

    public function getData(): mixed
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
