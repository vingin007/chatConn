<?php

return [
    'default' => [
        'host' => getenv('AMQP_HOST'),
        'port' => 5672,
        'user' => getenv('AMQP_USER'),
        'password' => getenv('AMQP_PASSWORD'),
        'vhost' => getenv('AMQP_VHOST'),
        'concurrent' => [
            'limit' => 1,
        ],
        'pool' => [
            'connections' => 1,
        ],
        'params' => [
            'insist' => false,
            'login_method' => 'AMQPLAIN',
            'login_response' => null,
            'locale' => 'en_US',
            'connection_timeout' => 3.0,
            'read_write_timeout' => 6.0,
            'context' => null,
            'keepalive' => false,
            'heartbeat' => 3,
            'close_on_destruct' => false,
        ],
    ],
];