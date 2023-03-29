<?php

namespace HyperfTest\Cases;

use App\Exception\BusinessException;
use App\Model\Order;
use App\Model\Package;
use App\Model\PaymentRecord;
use App\Model\User;
use App\Service\OrderService;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use HyperfTest\Factories\OrderFactory;
use PHPUnit\Framework\TestCase;

class OrderServiceTest extends TestCase
{
    protected OrderService $orderService;
    protected $redis;
    protected $user;
    protected $package;

    public function setUp(): void
    {
        parent::setUp();

        // 清空数据表
        Order::truncate();
        PaymentRecord::truncate();

        // 创建加量包
        $this->package = Package::query()->first();

        // 创建用户
        $this->user = User::create([
            'username' => '测试用户',
            'email' => 'test@example.com',
            'password' => '123456',
        ]);

        // 注入依赖
        $this->orderService = make(OrderService::class);
        $this->driverFactory = make(DriverFactory::class);
        $this->redis = ApplicationContext::getContainer()->get(Redis::class);
    }

    public function testGenerateOrderWithNoUnpaidOrders()
    {
        // 清空数据表
        Order::truncate();
        PaymentRecord::truncate();
        $user = $this->user;
        $package = $this->package;

        // 模拟redis中没有已付款订单
        $redisKey = 'paid_pool:' . $package->id;
        $this->redis->set($redisKey, '[]');

        // 模拟redis中没有已使用金额池
        $redisKey = 'used_pool:' . $package->id;
        $this->redis->set($redisKey, '[]');

        // 调用生成订单方法
        $orderService = make(OrderService::class);
        $order = $orderService->generateOrder($package, $user);

        // 断言订单价格为加量包原价
        $this->assertEquals($order->amount, $package->price);
    }
    /**
     * 测试有1个订单时，新创建订单价格是否为原价+0.01-0.14之间的随机值.
     */
    public function testGenerateOrderWithOneUnpaidOrder()
    {
        // 创建一个待付款订单
        $user = $this->user;
        $package = $this->package;
        $newOrder = $this->orderService->generateOrder($package, $user);
        $user = User::query()->find(10);
        // 调用生成订单方法
        $newOrder = $this->orderService->generateOrder($package, $user);

        // 断言新创建订单价格是否为原价+0.01-0.14之间的随机值
        $this->assertGreaterThan($package->price, $newOrder->price);
        $this->assertLessThanOrEqual($package->price + 0.14, $newOrder->price);
    }

}
