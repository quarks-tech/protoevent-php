<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Tests\Unit\Marshaler;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use QuarksTech\ProtoEvent\Event\Metadata;
use QuarksTech\ProtoEvent\Transport\Amqp\Marshaler\BinaryMarshaler;

final class BinaryMarshalerTest extends TestCase
{
    public function testMarshalUsesRfc3339WithoutFractionalSeconds(): void
    {
        $md = new Metadata(
            id: 'id-1',
            type: 'example.svc.v1.Event',
            source: 'test',
            time: new DateTimeImmutable('2026-01-06T12:34:56.123456+00:00'),
            specVersion: '1.0',
            dataContentType: 'application/proto',
        );

        $m = new BinaryMarshaler();
        $msg = $m->marshal($md, 'abc');

        self::assertArrayHasKey('attributes', $msg);
        self::assertArrayHasKey('headers', $msg['attributes']);

        $headers = $msg['attributes']['headers'];
        self::assertArrayHasKey('cloudEvents:time', $headers);
        self::assertIsString($headers['cloudEvents:time']);
        self::assertStringNotContainsString('.', $headers['cloudEvents:time']);
        self::assertSame('2026-01-06T12:34:56+00:00', $headers['cloudEvents:time']);
    }
}


