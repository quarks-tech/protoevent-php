<?php

namespace Quarks\Tests\EventBus;

use Example\Books\V1\BookCreatedEvent;
use Example\Books\V1\EventBus\Publisher\Publisher as BooksV1Publisher;
use PHPUnit\Framework\TestCase;
use Quarks\EventBus\Envelope;
use Quarks\EventBus\Metadata;
use Quarks\EventBus\Publisher;
use Quarks\EventBus\Transport\TransportInterface;

class PublisherTest extends TestCase
{
    private const UUID         = '859a8ad5-ad3f-475e-b2c2-38e568830631';
    private const PUBLISH_TIME = '2023-03-22T12:44:07+00:00';

    public function testPublish()
    {
        $transport = $this->getMockBuilder(TransportInterface::class)->getMock();
        $transport
            ->expects($this->once())
            ->method('publish')
            ->with(
                new Envelope(
                    (new Metadata('1.0', 'example.books.v1.BookCreated', 'protoevent-php', self::UUID, self::PUBLISH_TIME))
                        ->setDataContentType('application/cloudevents+json'),
                    '{"id":123}'
                )
            );

        $publisher = new Publisher($transport);
        $booksV1Publisher = new BooksV1Publisher($publisher);
        $booksV1Publisher->publishBookCreatedEvent(
            (new BookCreatedEvent())
                ->setId(123),
            ['id' => self::UUID, 'time' => self::PUBLISH_TIME]
        );
    }
}
