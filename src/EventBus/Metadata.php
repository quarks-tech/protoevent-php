<?php

namespace Quarks\EventBus;

class Metadata
{
    private string $specVersion;
    private string $type;
    private string $source;
    private string $id;
    private string $time;
    private string $subject = '';
    private string $dataScheme = '';
    private string $dataContentType = '';
    private array $extensions = [];

    public function __construct(string $specVersion, string $type, string $source, string $id, string $time)
    {
        $this->specVersion = $specVersion;
        $this->type = $type;
        $this->source = $source;
        $this->id = $id;
        $this->time = $time;
    }

    public function addExtension(string $name, string $value): self
    {
        $this->extensions[$name] = $value;

        return $this;
    }

    public function getSpecVersion(): string
    {
        return $this->specVersion;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTime(): string
    {
        return $this->time;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function setExtensions(array $extensions): self
    {
        $this->extensions = $extensions;

        return $this;
    }

    public function getDataScheme(): string
    {
        return $this->dataScheme;
    }

    public function setDataScheme(string $dataScheme): self
    {
        $this->dataScheme = $dataScheme;

        return $this;
    }

    public function getDataContentType(): string
    {
        return $this->dataContentType;
    }

    public function setDataContentType(string $dataContentType): self
    {
        $this->dataContentType = $dataContentType;

        return $this;
    }
}