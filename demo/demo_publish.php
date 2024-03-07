<?php

require_once "../vendor/autoload.php";

use Example\Books\V1\BookCreatedEvent;
use Example\Books\V1\EventBus\Publisher\Publisher as BooksV1Publisher;
use Quarks\EventBus\Publisher;

$config = require 'config.php';

$connection = new \Quarks\EventBus\Transport\AMQPConnection($config['amqp']);
$amqpTransport = new \Quarks\EventBus\Transport\AMQPTransport($connection, [
    'queue' => $config['protoevent']['queue'],
]);

$publisher = new Publisher($amqpTransport);

$booksV1Publisher = new BooksV1Publisher($publisher);
$booksV1Publisher->publishBookCreatedEvent(
    (new BookCreatedEvent())
        ->setId(312)
);
