<?php

declare(strict_types=1);

namespace App\Listener;

use App\Event\AudioMessageSend;
use App\Event\TransOrderPaid;
use Carbon\Carbon;
use Hyperf\Event\Annotation\Listener;
use Psr\Container\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class PaidUserListener implements ListenerInterface
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [
            OrderPaid::class
        ];
    }

    public function process(object $event): void
    {
        $order = $event->order;
        $user = $order->user()->first();
        if ($user->is_paid) {
            return;
        }
        $user->is_paid = true;
        $user->paid_time = Carbon::now('Asia/Shanghai');
        $user->save();
    }
}
