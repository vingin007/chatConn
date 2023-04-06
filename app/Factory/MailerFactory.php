<?php


namespace App\Factory;

use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;

class MailerFactory
{
    public function __invoke(ContainerInterface $container): MailerInterface
    {
        $dsn = config('mail.default.dsn');
        $transport = Transport::fromDsn($dsn);
        return new Mailer($transport);
    }
}
