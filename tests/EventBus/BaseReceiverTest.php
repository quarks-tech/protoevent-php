<?php

namespace Quarks\Tests\EventBus;
use Example\Books\V1\BookCreatedEvent;
use Example\Books\V1\BookUpdatedEvent;
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

        $receiver = new Receiver($transport, $dispatcher);
        $receiver->register(
            new EventDescriptor('example.books.v1.BookCreated', BookCreatedEvent::class)
        );

        $receiver->runAndStop();
    }

    public function testDispatchWithUnknownField()
    {
        $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                (new BookUpdatedEvent())->setId(777)
            );

        $transport = $this->getMockBuilder(TransportInterface::class)->getMock();
        $transport
            ->method('get')
            ->willReturn([
                new Envelope(
                    (new Metadata('1.0', 'example.books.v1.BookUpdated', '', '', ''))
                        ->setDataContentType('application/cloudevents+json'),
                    '{"id":777,"foo":"bar"}'
                )
            ]);

        $receiver = new Receiver($transport, $dispatcher);
        $receiver->register(
            new EventDescriptor('example.books.v1.BookUpdated', BookUpdatedEvent::class)
        );

        $receiver->runAndStop();
    }
}
