<?php

declare(strict_types=1);

namespace App\Job;

use App\Model\TransOrder;
use Hyperf\AsyncQueue\Job;

class CancelTransOrderJob extends Job
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
        $order = TransOrder::query()->where('order_no', $this->params)->first();
        if ($order->status == TransOrder::STATUS_UNPAID) {
            $order->status = TransOrder::STATUS_CANCELLED;
            $order->save();
        }
    }
}
