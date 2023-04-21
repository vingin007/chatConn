<?php

namespace App\Service;

use App\Event\TransOrderPaid;
use App\Exception\BusinessException;
use App\Job\CancelOrderJob;
use App\Model\Package;
use App\Model\Order;
use App\Model\PaymentRecord;
use App\Model\User;
use Carbon\Carbon;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Utils\Str;
use Psr\EventDispatcher\EventDispatcherInterface;

class OrderService
{
    const MAX_UNPAID_ORDERS = 10; // 最大未付款订单数
    const ORDER_AMOUNT_MIN = 0.01; // 订单最小金额
    const ORDER_AMOUNT_MAX = 0.09; // 订单最大金额

    protected $redis;

    protected DriverInterface $driver;
    #[Inject]
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(DriverFactory $driverFactory)
    {
        $this->redis = di()->get(Redis::class);
        $this->driver = $driverFactory->get('default');
    }

    public function generateOrder(Package $package, User $user)
    {
        $currentorder = Order::query()
            ->where('user_id', $user->id)
            ->where('package_id', $package->id)
            ->where('status', Order::STATUS_UNPAID)

            ->whereBetween('created_at', [Carbon::now('Asia/Shanghai')->subMinutes(5), Carbon::now('Asia/Shanghai')])
            ->first();
        if ($currentorder){
            throw new BusinessException(400, '您有未支付的订单，请先支付');
        }
        // 判断当前未付款订单数量是否已达到上限
        $unpaidOrders = Order::query()
            ->where('status', Order::STATUS_UNPAID)
            ->where('package_id', $package->id)
            ->whereBetween('created_at', [Carbon::now('Asia/Shanghai')->subMinutes(5), Carbon::now('Asia/Shanghai')])
            ->count();

        if ($unpaidOrders >= 10) {
            throw new BusinessException(400, '支付通道拥挤，请稍后再试');
        }

        // 从 Redis 中获取订单金额
        $amount = $this->redis->rPop("order:amounts:{$package->id}");
        if (!$amount) {
            throw new BusinessException(400, '支付通道拥挤，请稍后再试');
        }

        $order = new Order();
        $order->order_no = Str::random(20);
        $order->payment_method = 'wechat';
        $order->paid = false;
        $order->user_id = $user->id;
        $order->package_id = $package->id;
        $order->package_name = $package->name;
        $order->package_quota = $package->quota;
        $order->package_duration = $package->duration;
        $order->amount = $amount;
        $order->expired_at = Carbon::now('Asia/Shanghai')->addDays($package->duration);
        $order->status = Order::STATUS_UNPAID;

        if (!$order->save()) {
            $this->redis->lPush("order:amounts:{$package->id}", $amount);
            throw new BusinessException(400, '下单失败，请稍后再试');
        }
        $this->driver->push(new CancelOrderJob($order->order_no),60*100);
        return $order;
    }

    public function payOrder($amount,$sign,$timestamp)
    {
        $secret = 'woshigecaigou';
        $server_sign = new SignService($secret);
        $_sign = $server_sign->generateSign($timestamp);
        if($_sign != $sign){
            return false;
        }
        $carbonInstance = Carbon::createFromTimestamp($timestamp,'Asia/Shanghai');

        $currentCarbonInstance = $carbonInstance->now('Asia/Shanghai');
        $order = Order::query()->where('amount', $amount)
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
        $order->paid_time = Carbon::now('Asia/Shanghai');

        if (!$order->save()) {
            throw new BusinessException(400, 'Failed to pay order');
        }

        // 将订单金额返还到 Redis 中
        $this->redis->lPush("order:amounts:{$order->package_id}", $order->amount);
        $this->eventDispatcher->dispatch(new TransOrderPaid($order));
        return $order;
    }

    public function cancelOrder($orderId,User $user)
    {
        $order = Order::find($orderId);
        if (!$order) {
            throw new BusinessException(404, 'Order not found');
        }
        if ($order->user_id != $user->id) {
            throw new BusinessException(400, 'no permission');
        }
        if ($order->status != Order::STATUS_UNPAID) {
            throw new BusinessException(400, 'Order has been paid or canceled');
        }

        $order->status = Order::STATUS_CANCELLED;

        if (!$order->save()) {
            throw new BusinessException(400, 'Failed to cancel order');
        }

        // 将订单金额返还到 Redis 中
        $this->redis->lPush("order:amounts:{$order->package_id}", $order->amount);

        return $order;
    }
}
