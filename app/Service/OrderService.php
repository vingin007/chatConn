<?php

namespace App\Service;

use App\Event\OrderPaid;
use App\Event\TransOrderPaid;
use App\Exception\BusinessException;
use App\Job\CancelOrderJob;
use App\Model\Package;
use App\Model\Order;
use App\Model\PaymentRecord;
use App\Model\User;
use Carbon\Carbon;
use DateTime;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Hyperf\Utils\Str;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class OrderService
{
    const MAX_UNPAID_ORDERS = 10; // 最大未付款订单数
    const ORDER_AMOUNT_MIN = 0.01; // 订单最小金额
    const ORDER_AMOUNT_MAX = 0.09; // 订单最大金额

    protected $redis;
    protected LoggerInterface $logger;

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
        $order = new Order();
        $order->order_no = $this->generateOrderNo();
        $order->payment_method = 'wechat';
        $order->paid = false;
        $order->user_id = $user->id;
        $order->package_id = $package->id;
        $order->package_name = $package->name;
        $order->package_quota = $package->quota;
        $order->package_duration = $package->duration;
        $order->amount = $package->price;
        $order->expired_at = Carbon::now('Asia/Shanghai')->addDays($package->duration);
        $order->status = Order::STATUS_UNPAID;

        if (!$order->save()) {
            throw new BusinessException(400, '下单失败，请稍后再试');
        }
        $this->driver->push(new CancelOrderJob($order->order_no),60*10);
        return $order;
    }
    /**
     * 生成订单号
     *
     * @return string
     */
    private function generateOrderNo(): string
    {
        $now = new DateTime();
        $timestamp = $now->format('YmdHis');
        $random = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        return $timestamp . $random;
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
        $this->eventDispatcher->dispatch(new OrderPaid($order));
        return $order;
    }

    public function _payOrder($orderNo,$trade_no,$type,$money,LoggerFactory $loggerFactory)
    {
        $order = Order::query()->where('order_no', $orderNo)
            ->where('status', Order::STATUS_UNPAID)
            ->first();
        if (empty($order)){
            throw new BusinessException(400, '订单不存在');
        }
        if($money != $order->amount){
            throw new BusinessException(400, '支付金额不正确');
        }
        $this->logger = $loggerFactory->get('log', 'default');
        $this->logger->info($orderNo.'--'.$trade_no.'--'.$type.'--'.$money);
        try {
            $order->payment_method = $type;
            $order->trade_no = $trade_no;
            $order->status = Order::STATUS_PAID;
            $order->paid = true;
            $order->paid_time = Carbon::now('Asia/Shanghai');
            $order->save();
            $user = User::find($order->user_id);
            $user->quota += $order->package_quota;
            $user->save();
            $this->eventDispatcher->dispatch(new OrderPaid($order));
        } catch (\Exception $exception){
            throw new BusinessException(400, '支付失败');
        }
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
        return $order;
    }
}
