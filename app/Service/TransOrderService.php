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
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Utils\Str;
use Psr\EventDispatcher\EventDispatcherInterface;

class TransOrderService
{
    const MAX_UNPAID_ORDERS = 3; // 最大未付款订单数
    const ORDER_AMOUNT_MIN = 0.01; // 订单最小金额
    const ORDER_AMOUNT_MAX = 0.02; // 订单最大金额
    const PRICE_PER_SECOND = 0.02;
    protected $redis;

    protected DriverInterface $driver;
    #[Inject]
    protected EventDispatcherInterface $eventDispatcher;
    #[Inject]
    protected S3Service $s3Service;

    public function __construct(DriverFactory $driverFactory)
    {
        $this->redis = di()->get(Redis::class);
        $this->driver = $driverFactory->get('default');
    }

    public function generateOrder($file_id, User $user)
    {
        $file = Video::query()->where('id', $file_id)->first();
        $videoDuration = max($file->duration, 10); // 如果时长小于10秒，按10秒计算
        $orderAmount = $videoDuration * self::PRICE_PER_SECOND;

        // 判断当前未付款订单数量是否已达到上限
        $unpaidOrders = TransOrder::query()
            ->where('status', 0)
            ->where('order_amount', $orderAmount)
            ->whereBetween('created_at', [Carbon::now()->subMinutes(5), Carbon::now()])
            ->count();

        if ($unpaidOrders >= 4) {
            throw new BusinessException(400, '支付通道拥挤，请稍后再试');
        }
        if(!$this->redis->keys("trans_order:amounts:{$orderAmount}")){
            for ($i = 0; $i < 5; $i++) {
                $_amount = $orderAmount - $i / 100;
                $this->redis->lPush("trans_order:amounts:{$orderAmount}", $_amount);
            }
        }

        $amount = $this->redis->rPop("trans_order:amounts:{$orderAmount}");
        $order = new TransOrder();
        $order->user_id = $user->id;
        $order->original_video_store_name = $file->store_name;
        $order->video_duration = $videoDuration;
        $order->order_amount = $orderAmount;
        $order->amount = $amount;
        $order->original_video_id = $file_id;
        $order->video_size = $file->size;

        if ($order->save()) {
            $this->cancelOrder($order->id);
            return $order;
        }
        $this->redis->lPush("trans_order:amounts:{$orderAmount}", $order->amount);
        throw new BusinessException(400, '下单失败，请稍后再试');
    }
    public function payOrder($amount,$sign,$timestamp)
    {
        $secret = 'woshigecaigou';
        $server_sign = new SignService($secret);
        $_sign = $server_sign->generateSign($timestamp);
        if($_sign != $sign){
            return false;
        }
        $carbonInstance = Carbon::createFromTimestamp($timestamp);

        $currentCarbonInstance = $carbonInstance->now();
        $order = TransOrder::query()->where('amount', $amount)
            ->where('status', Order::STATUS_UNPAID)
            ->first();
        $payment = new PaymentRecord();
        if (!$order) {
            $payment->reason = '订单不存在';
            $payment->save();
            return false;
        }

        if ($order->status != Order::STATUS_UNPAID) {
            $payment->reason = '订单已支付或已取消';
            $payment->save();
        }
        $payment->reason = '';
        $payment->payment_order_no = $order->order_no;
        $payment->user_id = $order->user_id;
        $payment->save();
        $order->status = Order::STATUS_PAID;
        $order->paid_time = Carbon::now();

        if (!$order->save()) {
            throw new BusinessException(400, 'Failed to pay order');
        }

        // 将订单金额返还到 Redis 中
        $this->redis->lPush("trans_order:amounts:{$order->order_amount}", $order->amount);
        $video = Video::query()->where('id',$order->original_video_id)->first();
        $video->status = 1;
        $video->save();
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
        $this->redis->lPush("trans_order:amounts:{$order->order_amount}", $order->amount);
        return $order;
    }
}
