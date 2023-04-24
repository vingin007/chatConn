<?php

namespace App\Service;

use App\Event\TransOrderPaid;
use App\Exception\BusinessException;
use App\Job\CancelOrderJob;
use App\Model\Package;
use App\Model\Order;
use App\Model\PaymentRecord;
use App\Model\TransOrder;
use App\Model\User;
use App\Model\Video;
use Aws\S3\Exception\S3Exception;
use Carbon\Carbon;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Hyperf\AsyncQueue\Annotation\AsyncQueueMessage;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Utils\Str;
use Psr\EventDispatcher\EventDispatcherInterface;

class TransOrderService
{
    protected $redis;

    protected DriverInterface $driver;
    #[Inject]
    protected EventDispatcherInterface $eventDispatcher;
    #[Inject]
    protected S3Service $s3Service;

    const AMOUNT_PER_MINUTE = 0.6;

    public function __construct(DriverFactory $driverFactory)
    {
        $this->redis = di()->get(Redis::class);
        $this->driver = $driverFactory->get('default');
    }

    public function generateOrder($file_id, User $user)
    {
        $file = Video::query()->where('id', $file_id)->first();
        $minutes = ceil($file->duration/60);
        $quota = User::query()->where('id', $user->id)->value('quota');
        if($quota < $minutes){
            throw new BusinessException(400, '您的剩余时长不足，请充值后再试');
        }
        $orderAmount = $minutes * self::AMOUNT_PER_MINUTE;
        Db::beginTransaction();
        try {
            $order = new TransOrder();
            $order->user_id = $user->id;
            $order->original_video_store_name = $file->store_name;
            $order->video_duration = $minutes;
            $order->real_duration = $file->duration;
            $order->order_amount = $orderAmount;
            $order->amount = $orderAmount;
            $order->original_video_id = $file_id;
            $order->video_size = $file->size;
            $order->status = 1;
            if (!$order->save()) {
                   throw new BusinessException(400, '创建订单失败');
            }
            $user->quota -= $minutes;
            $user->save();
            Db::commit();
        }catch (\Exception|BusinessException $e){
            Db::rollBack();
        }
        return $order;
    }
    #[AsyncQueueMessage(delay: 60 * 5)]
    public function cancelOrder($orderId)
    {
        $order = TransOrder::find($orderId);
        if (!$order) {
            throw new BusinessException(404, 'Order not found');
        }
        if ($order->status != Order::STATUS_UNPAID) {
            throw new BusinessException(400, 'Order has been paid or canceled');
        }
        $order->status = Order::STATUS_CANCELLED;

        if (!$order->save()) {
            throw new BusinessException(400, 'Failed to cancel order');
        }
        return $order;
    }
}
