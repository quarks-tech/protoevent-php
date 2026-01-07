<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Tests\Unit\Publisher;

use Google\Protobuf\Internal\Message;
use PHPUnit\Framework\TestCase;
use QuarksTech\ProtoEvent\Event\Metadata;
use QuarksTech\ProtoEvent\EventBus\PublishOption\WithContentType;
use QuarksTech\ProtoEvent\EventBus\Publisher;
use QuarksTech\ProtoEvent\EventBus\ServiceDesc;
use QuarksTech\ProtoEvent\Transport\SenderInterface;

final class PublisherContentTypeCodecSelectionTest extends TestCase
{
    public function testWithContentTypeCloudeventsJsonSelectsJsonCodecEncoding(): void
    {
        $sender = new class implements SenderInterface {
            public ?Metadata $metadata = null;
            public ?string $data = null;

            public function send(Metadata $metadata, string $data): void
            {
                $this->metadata = $metadata;
                $this->data = $data;
            }

            public function setup(ServiceDesc $serviceDesc): void
            {
            }
        };

        $publisher = new Publisher($sender);

        /** @var Message&\PHPUnit\Framework\MockObject\MockObject $msg */
        $msg = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['serializeToString', 'serializeToJsonString'])
            ->getMock();

        $msg->method('serializeToString')->willReturn("BIN");
        $msg->method('serializeToJsonString')->willReturn('{"x":1}');

        $publisher->publish(
            'example.svc.v1.Event',
            $msg,
            new WithContentType('application/cloudevents+json')
        );

        self::assertNotNull($sender->metadata);
        self::assertSame('application/cloudevents+json', $sender->metadata->getDataContentType());
        self::assertSame('{"x":1}', $sender->data);
    }
}


