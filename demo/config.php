<?php

return [
    'amqp' => [
        'host' => getenv('RABBITMQ_HOST'),
        'port' => getenv('RABBITMQ_PORT'),
        'login' => getenv('RABBITMQ_USER'),
        'password' => getenv('RABBITMQ_PASS'),
        'vhost' => '/'
    ],
    'protoevent' => [
        'queue' => 'namespace.service.consumers.v1'
    ]
];