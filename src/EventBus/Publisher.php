<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus;

use Google\Protobuf\Internal\Message;
use QuarksTech\ProtoEvent\Encoding\CodecInterface;
use QuarksTech\ProtoEvent\Encoding\CodecRegistry;
use QuarksTech\ProtoEvent\Encoding\ProtobufCodec;
use QuarksTech\ProtoEvent\Event\Metadata;
use QuarksTech\ProtoEvent\Transport\SenderInterface;

/**
 * Default implementation of EventPublisher
 */
final class Publisher implements EventPublisher
{
    private SenderInterface $sender;
    private CodecInterface $codec;
    private CodecRegistry $codecRegistry;
    /** @var PublishOption[] */
    private array $defaultOptions = [];
    /** @var PublisherInterceptor[] */
    private array $interceptors = [];

    public function __construct(SenderInterface $sender, ?CodecInterface $codec = null)
    {
        $this->sender = $sender;
        $this->codec = $codec ?? new ProtobufCodec();
        $this->codecRegistry = new CodecRegistry();
        // Ensure custom codecs provided to the publisher can also be selected by content type options.
        $this->codecRegistry->register($this->codec);
    }

    public function publish(string $eventType, Message $event, PublishOption ...$options): void
    {
        $metadata = Metadata::create($eventType);

        // Set default content type based on codec
        $metadata = $metadata->withDataContentType($this->codec->getContentType());

        // Apply default options first
        foreach ($this->defaultOptions as $option) {
            $metadata = $option->apply($metadata);
        }

        // Apply provided options
        foreach ($options as $option) {
            $metadata = $option->apply($metadata);
        }

        // Build interceptor chain
        $handler = function (Metadata $md, Message $evt): void {
            // Match protoevent-go behavior: content type drives both marshaling mode and codec choice.
            // E.g. application/cloudevents+json => JSON encoding for structured mode.
            $codec = $this->codecRegistry->hasCodec($md->getDataContentType())
                ? $this->codecRegistry->getCodec($md->getDataContentType())
                : $this->codec;

            $data = $codec->encode($evt);
            $this->sender->send($md, $data);
        };

        $chain = $handler;
        foreach (array_reverse($this->interceptors) as $interceptor) {
            $next = $chain;
            $chain = static function (Metadata $md, Message $evt) use ($interceptor, $next): void {
                $interceptor->intercept($md, $evt, $next);
            };
        }

        $chain($metadata, $event);
    }

    public function withDefaultOptions(PublishOption ...$options): self
    {
        $this->defaultOptions = array_merge($this->defaultOptions, $options);
        return $this;
    }

    public function withInterceptor(PublisherInterceptor $interceptor): self
    {
        $this->interceptors[] = $interceptor;
        return $this;
    }

    public function withCodec(CodecInterface $codec): self
    {
        $this->codec = $codec;
        $this->codecRegistry->register($codec);
        return $this;
    }
}
