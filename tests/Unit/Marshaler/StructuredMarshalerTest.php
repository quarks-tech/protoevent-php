<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Tests\Unit\Marshaler;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use QuarksTech\ProtoEvent\Event\Metadata;
use QuarksTech\ProtoEvent\Transport\Amqp\Marshaler\StructuredMarshaler;

final class StructuredMarshalerTest extends TestCase
{
    public function testMarshalIncludesDatacontenttypeForGoSubscriberCompatibility(): void
    {
        $md = new Metadata(
            id: 'id-1',
            type: 'example.svc.v1.Event',
            source: 'test',
            time: new DateTimeImmutable('2026-01-06T12:34:56.123456+00:00'),
            specVersion: '1.0',
            dataContentType: 'application/cloudevents+json',
        );

        $m = new StructuredMarshaler();
        $msg = $m->marshal($md, '{"a":1}');

        self::assertSame('application/cloudevents+json', $msg['attributes']['content_type']);

        $event = json_decode($msg['body'], true);
        self::assertIsArray($event);
        self::assertSame('application/cloudevents+json', $event['datacontenttype'] ?? null);
        self::assertStringNotContainsString('.', $event['time'] ?? '');
        self::assertSame('2026-01-06T12:34:56+00:00', $event['time'] ?? null);
    }
}


