<?php

namespace Quarks\Tests\EventBus;
use Example\Books\V1\BookCreatedEvent;
use PHPUnit\Framework\TestCase;
use Quarks\EventBus\Descriptor\EventDescriptor;
use Quarks\EventBus\Dispatcher\Dispatcher;
use Quarks\EventBus\Envelope;
use Quarks\EventBus\Metadata;
use Quarks\EventBus\Receiver;
use Quarks\EventBus\Transport\TransportInterface;

class BaseReceiverTest extends TestCase
{
    public function testDispatch()
    {
        $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                (new BookCreatedEvent())->setId(123)
            );

        $transport = $this->getMockBuilder(TransportInterface::class)->getMock();
        $transport
            ->method('get')
            ->willReturn([
                new Envelope(
                    (new Metadata('1.0', 'example.books.v1.BookCreated', '', '', ''))
                        ->setDataContentType('application/cloudevents+json'),
                    '{"id":123}'
                )
            ]);

        $a = new Receiver($transport, $dispatcher);
        $a->register(
            new EventDescriptor('example.books.v1.BookCreated', BookCreatedEvent::class)
        );

        $a->runAndStop();
    }
}
