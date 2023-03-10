<?php

namespace Quarks\Tests\EventBus\Encoding;

use PHPUnit\Framework\TestCase;
use Quarks\EventBus\CloudEvent;
use Quarks\EventBus\Encoding\ProtoJsonEncoder;

class ProtoJsonEncoderTest extends TestCase
{
    public function testEncode()
    {
        $encoder = new ProtoJsonEncoder();
        $encoded = $encoder->encode(
            new CloudEvent(
                '1df6bf78-47c7-468f-9c8b-b15379ad8a8e',
                'protoevent-php',
                'example.books.v1.BookCreated',
                ['id' => 1456],
                'application/cloudevents+json',
                date_create_immutable('2023-03-03T12:16:39+00:00'),
            )
        );

        $this->assertEquals(
            '{"source":"protoevent-php","data":{"id":1456},"datacontenttype":"application/cloudevents+json","time":"2023-03-03T12:16:39+00:00","specversion":"1.0","id":"1df6bf78-47c7-468f-9c8b-b15379ad8a8e","type":"example.books.v1.BookCreated"}',
            $encoded
        );
    }

    public function testDecode()
    {
        $encoder = new ProtoJsonEncoder();
        $encoded = $encoder->decode('{"source":"protoevent-php","data":{"id":1456},"datacontenttype":"application/cloudevents+json","time":"2023-03-03T12:16:39+00:00","specversion":"1.0","id":"1df6bf78-47c7-468f-9c8b-b15379ad8a8e","type":"example.books.v1.BookCreated"}');

        $this->assertEquals(
            new CloudEvent(
                '1df6bf78-47c7-468f-9c8b-b15379ad8a8e',
                'protoevent-php',
                'example.books.v1.BookCreated',
                ['id' => 1456],
                'application/cloudevents+json',
                date_create_immutable('2023-03-03T12:16:39+00:00'),
            ),
            $encoded
        );
    }
}
