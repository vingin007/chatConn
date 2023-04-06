<?php

declare(strict_types=1);

namespace App\Controller;

use App\Event\OrderPaid;
use App\Exception\BusinessException;
use App\Middleware\Auth\AdminAuthMiddleware;
use App\Model\Order;
use App\Model\PaymentRecord;
use App\Traits\ApiResponseTrait;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Paginator\Paginator;
use Psr\EventDispatcher\EventDispatcherInterface;

#[Controller]
#[Middlewares([AdminAuthMiddleware::class])]
class AdminPaymentController
{
    use ApiResponseTrait;
    #[Inject]
    protected EventDispatcherInterface $eventDispatcher;
    #[RequestMapping(path: 'lists', methods: 'get')]
    public function index(RequestInterface $request)
    {
        $currentPage = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);
        $payments = PaymentRecord::with('user')->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $currentPage);
        return $this->success(new Paginator($payments, $perPage, $currentPage));
    }
    #[RequestMapping(path: 'detail', methods: 'get')]
    public function show(RequestInterface $request)
    {
        $payment = PaymentRecord::with('user')->find($request->input('id'));
        return $this->success($payment);
    }
    #[RequestMapping(path: 'pay', methods: 'post')]
    public function payOrder(RequestInterface $request)
    {
        // 获取订单信息
        $order = Order::query()->where('order_no', $request->input('order_no'))->first();
        if (!$order) {
            throw new BusinessException(400, '订单不存在');
        }

        // 获取支付流水单信息
        $paymentRecord = PaymentRecord::query()->where('id', $request->input('payment_record_id'))->first();
        if (!$paymentRecord) {
            throw new BusinessException(400, '支付流水单不存在');
        }
        // 开始事务
        Db::beginTransaction();
        try {
            // 将订单标记为已付款
            $order->status = Order::STATUS_PAID;
            $order->amount = $paymentRecord->payment_amount;
            $order->payment_method = 'wechat';
            $order->paid = true;
            $order->paid_at = $paymentRecord->payment_time;
            if (!$order->save()) {
                throw new BusinessException(400, '订单支付失败');
            }

            // 将支付流水单标记为已付款
            $paymentRecord->payment_order_no = $order->order_no;
            $paymentRecord->user_id = $order->user_id;
            $paymentRecord->save();
            $this->eventDispatcher->dispatch(new OrderPaid($order));
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollBack();
            throw new BusinessException(400, '订单支付失败：' . $e->getMessage());
        }
        return $this->success($order);
    }
}
