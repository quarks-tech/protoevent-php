<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Tests\Unit\Transport;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use QuarksTech\ProtoEvent\Event\Metadata;
use QuarksTech\ProtoEvent\EventBus\EventDesc;
use QuarksTech\ProtoEvent\EventBus\ServiceDesc;
use QuarksTech\ProtoEvent\Transport\InMemory\InMemoryTransport;

final class InMemoryTransportTest extends TestCase
{
    public function testSendStoresMessages(): void
    {
        $transport = new InMemoryTransport();

        $metadata = new Metadata(
            id: 'test-id',
            type: 'example.v1.TestEvent',
            source: 'test',
            time: new DateTimeImmutable(),
        );

        $transport->send($metadata, 'test-data');

        self::assertSame(1, $transport->count());
        self::assertCount(1, $transport->getMessages());

        $messages = $transport->getMessages();
        self::assertSame($metadata, $messages[0]['metadata']);
        self::assertSame('test-data', $messages[0]['data']);
    }

    public function testReceiveProcessesMessages(): void
    {
        $transport = new InMemoryTransport();

        $metadata = new Metadata(
            id: 'test-id',
            type: 'example.v1.TestEvent',
            source: 'test',
            time: new DateTimeImmutable(),
        );

        $transport->send($metadata, 'data-1');
        $transport->send($metadata, 'data-2');

        $processed = [];
        $transport->receive(function (Metadata $md, string $data) use (&$processed): void {
            $processed[] = $data;
        });

        self::assertSame(['data-1', 'data-2'], $processed);
        self::assertSame(0, $transport->count());
    }

    public function testSetupRegistersService(): void
    {
        $transport = new InMemoryTransport();

        $serviceDesc = new ServiceDesc(
            serviceName: 'example.v1',
            events: [],
            metadata: '',
        );

        $transport->setup($serviceDesc);

        self::assertTrue($transport->hasService('example.v1'));
        self::assertFalse($transport->hasService('unknown'));
    }

    public function testClearRemovesMessages(): void
    {
        $transport = new InMemoryTransport();

        $metadata = new Metadata(
            id: 'test-id',
            type: 'example.v1.TestEvent',
            source: 'test',
            time: new DateTimeImmutable(),
        );

        $transport->send($metadata, 'test-data');
        self::assertSame(1, $transport->count());

        $transport->clear();
        self::assertSame(0, $transport->count());
    }

    public function testResetClearsEverything(): void
    {
        $transport = new InMemoryTransport();

        $metadata = new Metadata(
            id: 'test-id',
            type: 'example.v1.TestEvent',
            source: 'test',
            time: new DateTimeImmutable(),
        );

        $serviceDesc = new ServiceDesc(
            serviceName: 'example.v1',
            events: [],
            metadata: '',
        );

        $transport->send($metadata, 'test-data');
        $transport->setup($serviceDesc);

        $transport->reset();

        self::assertSame(0, $transport->count());
        self::assertFalse($transport->hasService('example.v1'));
    }
}
