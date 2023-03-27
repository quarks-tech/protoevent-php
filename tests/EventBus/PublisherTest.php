<?php

namespace Quarks\Tests\EventBus;

use Example\Books\V1\BookCreatedEvent;
use PHPUnit\Framework\TestCase;
use Quarks\EventBus\Envelope;
use Quarks\EventBus\Metadata;
use Quarks\EventBus\Publisher;
use Quarks\EventBus\Transport\TransportInterface;

class PublisherTest extends TestCase
{
    public function testPublish()
    {
        $transport = $this->getMockBuilder(TransportInterface::class)->getMock();
        $transport
            ->expects($this->once())
            ->method('publish')
            ->with(
                new Envelope(
                    (new Metadata('1.0', 'example.books.v1.BookCreated', 'protoevent-php', '859a8ad5-ad3f-475e-b2c2-38e568830631', '2023-03-22T12:44:07+00:00'))
                        ->setDataContentType('application/cloudevents+json'),
                    '{"id":123}'
                )
            );

        $publisher = new Publisher($transport);
        $publisher->publish((new BookCreatedEvent())->setId(123), 'example.books.v1.BookCreated');
    }
}
