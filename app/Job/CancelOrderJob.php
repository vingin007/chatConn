<?php

declare(strict_types=1);

namespace App\Job;

use App\Model\Order;
use Hyperf\AsyncQueue\Job;

class CancelOrderJob extends Job
{
    public $params;

    /**
     * 任务执行失败后的重试次数，即最大执行次数为 $maxAttempts+1 次
     */
    protected int $maxAttempts = 2;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $order = Order::query()->where('order_no', $this->params)->first();
        if ($order->status == Order::STATUS_UNPAID) {
            $order->status = Order::STATUS_CANCELLED;
            $order->save();
        }
    }
}
