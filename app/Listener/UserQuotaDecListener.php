<?php

declare(strict_types=1);

namespace App\Listener;

use App\Event\AudioMessageSend;
use App\Event\TextMessageSend;
use Hyperf\Event\Annotation\Listener;
use Psr\Container\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class UserQuotaDecListener implements ListenerInterface
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [
            TextMessageSend::class,
            AudioMessageSend::class
        ];
    }

    public function process(object $event): void
    {
        $user = $event->user;
        $user->quota = $user->quota-1;
        $user->save();
    }
}
