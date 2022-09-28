<?php

require_once "../vendor/autoload.php";

use Example\Books\V1\BookCreatedEvent;
use Example\Books\V1\EventBus\Publisher\Publisher as BooksV1Publisher;
use Quarks\EventBus\Encoding\ProtoJsonEncoder;
use Quarks\EventBus\Publisher;
use Quarks\EventBus\Transport\ArrayTransport;

$amqpTransport = new ArrayTransport();
$publisher = new Publisher($amqpTransport, new ProtoJsonEncoder());

$booksV1Publisher = new BooksV1Publisher($publisher);
$booksV1Publisher->publishBookCreatedEvent(
    (new BookCreatedEvent())
        ->setId(312)
);
