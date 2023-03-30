<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\BusinessException;
use App\Middleware\Auth\AdminAuthMiddleware;
use App\Model\Order;
use App\Model\Package;
use App\Model\User;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Paginator\Paginator;
use Hyperf\Utils\Str;

#[Controller]
#[Middlewares([AdminAuthMiddleware::class])]
class AdminUserController
{
    use ApiResponseTrait;
    /**
     * @PostMapping(path="assign-package")
     */
    public function assignPackage(RequestInterface $request, ResponseInterface $response)
    {
        $userId = $request->input('user_id');
        $packageId = $request->input('package_id');
        $paymentMethod = $request->input('payment_method', Order::PAYMENT_METHOD_FREE);

        $user = User::find($userId);
        if (!$user) {
            throw new BusinessException(404, 'User not found');
        }

        $package = Package::find($packageId);
        if (!$package) {
            throw new BusinessException(404, 'Package not found');
        }

        $amount = $paymentMethod == Order::PAYMENT_METHOD_FREE ? 0 : $package->price;

        // 创建订单
        $order = new Order();
        $order->order_no = Str::random(20);
        $order->user_id = $userId;
        $order->payment_method = $paymentMethod;
        $order->paid = true;
        $order->paid_time = Carbon::now();
        $order->package_id = $package->id;
        $order->package_name = $package->name;
        $order->package_quota = $package->quota;
        $order->package_duration = $package->duration;
        $order->amount = $amount;
        $order->expired_at = Carbon::now()->addDays($package->duration);
        $order->status = Order::STATUS_PAID;
        $order->save();

        // 更新用户配额和过期时间
        $user->quota += $package->quota;
        $user->level = $package->level;
        $user->expire_time = max($user->expire_time, $order->expired_at);
        $user->save();

        return $this->success($order);
    }

    #[RequestMapping(path: 'users', methods: 'get')]
    public function getUsers(RequestInterface $request, ResponseInterface $response)
    {
        $currentPage = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);
        $users = User::query()->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $currentPage);
        return $this->success(new Paginator($users, $perPage, $currentPage));
    }

    /**
     * @GetMapping(path="users/{id}")
     */
    #[RequestMapping(path: 'detail', methods: 'get')]
    public function getUserDetail(RequestInterface $request, ResponseInterface $response)
    {
        $userId = $request->input('id');
        $user = User::find($userId);
        if (!$user) {
            throw new BusinessException(404, 'User not found');
        }

        $orders = $user->orders()->orderByDesc('created_at')->where('status', Order::STATUS_PAID)->get();

        $data = [
            'user' => $user,
            'orders' => $orders,
        ];

        return $this->success($data);
    }
}
