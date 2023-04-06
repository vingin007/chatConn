<?php
use Symfony\Component\Mailer\Mailer;

return [
    'default' => [
        'dsn' => env('MAILER_DSN'),
    ],
    'mailer' => Mailer::class,
];
