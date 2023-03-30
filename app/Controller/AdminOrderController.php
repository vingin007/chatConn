<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\BusinessException;
use App\Middleware\Auth\AdminAuthMiddleware;
use App\Model\Order;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Paginator\Paginator;

#[Controller]
#[Middlewares([AdminAuthMiddleware::class])]
class AdminOrderController
{
    use ApiResponseTrait;
    #[RequestMapping(path: 'orders', methods: 'get')]
    public function index(RequestInterface $request)
    {
        $currentPage = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);
        $orders = Order::with('user')->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $currentPage);
        return $this->success(new Paginator($orders, $perPage, $currentPage));
    }
    #[RequestMapping(path: 'show', methods: 'get')]
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
        $order->paid_time = Carbon::now();
        $order->payment_method = $request->input('payment_method');
        if (!$order->save()) {
            throw new BusinessException(400, '标记订单为已付款失败');
        }

        return $this->success($order);
    }
}
