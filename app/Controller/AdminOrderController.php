<?php

declare(strict_types=1);

namespace App\Controller;

use App\Event\TransOrderPaid;
use App\Exception\BusinessException;
use App\Middleware\Auth\AdminAuthMiddleware;
use App\Model\Order;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Paginator\Paginator;
use Psr\EventDispatcher\EventDispatcherInterface;

#[Controller]
#[Middlewares([AdminAuthMiddleware::class])]
class AdminOrderController
{
    use ApiResponseTrait;
    #[Inject]
    protected EventDispatcherInterface $eventDispatcher;
    #[RequestMapping(path: 'orders', methods: 'get')]
    public function index(RequestInterface $request)
    {
        $user_id = $request->input('user_id', '');
        $mobile = $request->input('mobile', '');
        $order_no = $request->input('order_no', '');
        $currentPage = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);
        $orderModel = Order::with('user');
        if (!empty($mobile)) {
            $orderModel = $orderModel->where('order_no', 'like', "%{$mobile}%");
        }
        if (!empty($user_id)) {
            $orderModel = $orderModel->where('user_id', $user_id);
        }
        if(!empty($order_no)) {
            $orderModel = $orderModel->where('order_no', 'like', "%{$order_no}%");
        }
        $orders = $orderModel->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $currentPage);
        return $this->success(new Paginator($orders, $perPage, $currentPage));
    }
    #[RequestMapping(path: 'detail', methods: 'get')]
    public function show(RequestInterface $request)
    {
        $order = Order::with('user')->find($request->input('id'));
        return $this->success($order);
    }
    #[RequestMapping(path: 'paid', methods: 'post')]
    public function paid(RequestInterface $request)
    {
        $order = Order::query()->findOrFail($request->input('order_no'));
        if ($order->status == Order::STATUS_PAID) {
            throw new BusinessException(400, '订单已付款');
        }

        $order->status = Order::STATUS_PAID;
        $order->paid_time = Carbon::now('Asia/Shanghai');
        $order->payment_method = $request->input('payment_method');
        if (!$order->save()) {
            throw new BusinessException(400, '标记订单为已付款失败');
        }
        $this->eventDispatcher->dispatch(new TransOrderPaid($order));
        return $this->success($order);
    }
}
