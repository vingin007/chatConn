<?php

declare(strict_types=1);

namespace App\Listener;

use App\Event\AudioMessageSend;
use App\Event\TransOrderPaid;
use App\Event\TextMessageSend;
use App\Exception\BusinessException;
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
            AudioMessageSend::class,
            TextMessageSend::class
        ];
    }

    public function process(object $event): void
    {
        $user = $event->user;
        $user->quota -= 1;
        if($user->quota < 0){
            throw new BusinessException('余额不足');
        }
        $user->save();
    }
}
