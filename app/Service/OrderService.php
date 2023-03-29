<?php

namespace App\Service;

use App\Exception\BusinessException;
use App\Job\CancelOrderJob;
use App\Model\Package;
use App\Model\Order;
use App\Model\PaymentRecord;
use App\Model\User;
use Carbon\Carbon;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Redis\Redis;
use Throwable;

class OrderService
{
    protected DriverInterface $driver;
    protected $redis;

    public function __construct(DriverFactory $driverFactory)
    {
        $this->redis = di()->get(Redis::class);
        $this->driver = $driverFactory->get('default');
    }

    /**
     * 生成加量包订单.
     *
     * @param int $packageId 加量包ID
     * @param int $userId 用户ID
     * @return Order
     */
    public function generateOrder(Package $package, User $user): Order
    {
        // 查询当前加量包的所有待付款订单
        $unpaidOrders = Order::query()
            ->where('package_id', $package->id)
            ->where('status', Order::STATUS_UNPAID)
            ->orderBy('amount', 'asc')
            ->get();

        // 获取已付款金额池和已使用金额池
        $paidPool = $this->getPaidPool($package->id);
        $usedPool = $this->getUsedPool($package->id);

        // 判断待付款订单数量是否达到上限
        if (count($unpaidOrders) >= 15) {
            // 提示用户支付通道拥挤，无法购买
            throw new BusinessException('支付通道拥挤，无法购买');
        }

        $order = new Order();
        $order->order_no = $this->generateOrderNo($user->id);
        $order->user_id = $user->id;
        $order->paid = 0;
        $order->package_id = $package->id;
        $order->package_name = $package->name;
        $order->package_quota = $package->quota;
        $order->package_duration = $package->duration;
        $order->expired_at = Carbon::now()->addDays(30);
        $order->status = Order::STATUS_UNPAID;
        // 检查是否存在已付款订单
        if (count($usedPool) > 0) {
            // 从已付款金额池中选择一个未被使用过的金额
            $unusedPrice = $this->chooseUnusedPrice($paidPool, $usedPool);
            $order->amount = $unusedPrice;
            $order->save();
            $this->updateUsedPool($package->id);
        } else {
            // 分配新的随机金额给待付款订单
            if (count($usedPool) > 0) {
                $order->amount = $this->getUnusedPrice($usedPool);
            } else {
                $order->amount = $this->getRandomAddPrice($usedPool);
            }
            $order->save();
        }
        $this->driver->push(new CancelOrderJob($order->order_no), 60*3);
        return $order;
    }
    /**
     * 获取加量包原价.
     *
     * @param int $packageId 加量包ID
     * @return float
     */
    public function getPackagePrice(int $packageId): float
    {
        $package = Package::query()->findOrFail($packageId);
        return $package->price;
    }

    /**
     * 手动处理支付
     * @param Order $order
     * @param PaymentRecord $payment_record
     * @return Order
     */
    public function handlePayment(Order $order,PaymentRecord $payment_record)
    {
        Db::beginTransaction();
        try {
            $order->paid = $payment_record->payment_amount;
            $order->status = Order::STATUS_PAID;
            $order->save();
            $payment_record->payment_order_no = $order->order_no;
            $payment_record->user_id = $order->user_id;
            $payment_record->save();
            Db::commit();
        }catch (Throwable $e) {
            Db::rollBack();
            throw new BusinessException('订单支付失败');
        }
        return $order;
    }
    /**
     * 订单支付成功后的处理.
     *
     * @param Order $order 订单
     * @param float $paidAmount 支付金额
     * @return true
     */
    public function callbackPayment(float $paidAmount)
    {
        $record = new PaymentRecord();
        $record->payment_time = Carbon::now();
        try {
            $order = Order::query()->where('amount', $paidAmount)->where('status',0)->firstOrFail();
            $order->paid = $paidAmount;
            $order->status = Order::STATUS_PAID;
            $order->save();
        } catch (Throwable $e) {
            // 记录异常付款
            $record->payment_amount = $paidAmount;
            $record->save();
            return true;
        }
        $record->payment_order_no = $order->order_no;
        $record->user_id = $order->user_id;
        $record->amount = $paidAmount;
        $record->save();
        $packageId = $order->package_id;
        $this->updatePaidPool($packageId);
        $this->updateUsedPool($packageId);
        return true;
    }
    public function getRecentOrders(int $packageId,$status): array
    {
        date_default_timezone_set('Asia/Shanghai');
        $now = Carbon::now()->toDateTimeString();
        $fiveMinutesAgo = Carbon::now()->subMinutes(5)->toDateTimeString();
        $orders = Order::query()
            ->whereBetween('created_at', [$fiveMinutesAgo, $now])
            ->where('status', $status)
            ->where('package_id', $packageId)
            ->pluck('amount')
            ->toArray();
        $uniqueOrders = array_unique($orders);
        return $uniqueOrders;
    }

    /**
     * 更新已付款金额池.
     *
     * @param int $packageId 加量包ID
     */
    private function updatePaidPool(int $packageId): void
    {
        $redisKey = $this->getPaidPoolRedisKey($packageId);
        $paidPool = $this->getRecentOrders($packageId,Order::STATUS_PAID);
        $this->redis->set($redisKey, json_encode($paidPool));
    }

    /**
     * 更新已使用金额池.
     *
     * @param int $packageId 加量包ID
     */
    private function updateUsedPool(int $packageId) : void
    {
        $redisKey = $this->getUsedPoolRedisKey($packageId);
        $usedPool = $this->getRecentOrders($packageId,Order::STATUS_UNPAID);
        $this->redis->set($redisKey, json_encode($usedPool));
    }
    /**
     * 获取已付款金额池在 Redis 中的键名.
     *
     * @param int $packageId 加量包ID
     * @return string
     */
    private function getPaidPoolRedisKey(int $packageId): string
    {
        return sprintf('paid_pool:%d', $packageId);
    }

    /**
     * 获取已使用金额池在 Redis 中的键名.
     *
     * @param int $packageId 加量包ID
     * @return string
     */
    private function getUsedPoolRedisKey(int $packageId): string
    {
        return sprintf('used_pool:%d', $packageId);
    }
    /**
     * 从已付款金额池中选择一个未被使用过的金额.
     *
     * @param array $paidPool 已付款金额池
     * @param array $usedPool 已使用金额池
     * @return float
     */
    private function chooseUnusedPrice(array $paidPool, array $usedPool): float
    {
        $unusedPool = array_diff($paidPool, $usedPool);
        if (count($unusedPool) == 0) {
            return 0;
        }
        return array_values($unusedPool)[0];
    }

    /**
     * 获取一个随机的加价金额.
     *
     * @param array $usedPool 已使用金额池
     * @return float
     */
    private function getRandomAddPrice(array $usedPool): float
    {
        $addPrice = 0.01 * rand(1, 14);
        while (in_array($addPrice, $usedPool)) {
            $addPrice = 0.01 * rand(1, 14);
        }
        return $addPrice;
    }

    /**
     * 获取已付款金额池.
     *
     * @param int $packageId 加量包ID
     * @return array
     */
    private function getPaidPool(int $packageId): array
    {
        $redisKey = $this->getPaidPoolRedisKey($packageId);
        $paidPoolJson = $this->redis->get($redisKey);
        $paidPool = $paidPoolJson ? json_decode($paidPoolJson, true) : [];

        return $paidPool;
    }

    /**
     * 获取已使用金额池.
     *
     * @param int $packageId 加量包ID
     * @return array
     */
    private function getUsedPool(int $packageId): array
    {
        $redisKey = $this->getUsedPoolRedisKey($packageId);
        $usedPoolJson = $this->redis->get($redisKey);
        $usedPool = $usedPoolJson ? json_decode($usedPoolJson, true) : [];

        return $usedPool;
    }
    function generateOrderNo($userId) {
        $timestamp = time();
        $random = mt_rand(10000, 99999);
        return sprintf('%s%05d%s', date('YmdHis', $timestamp), $userId, $random);
    }
}
