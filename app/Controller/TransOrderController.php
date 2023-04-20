<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\Auth\RefreshTokenMiddleware;
use App\Model\TransOrder;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use HyperfExtension\Auth\AuthManager;


#[Controller]
#[Middlewares([
    RefreshTokenMiddleware::class
])]
class TransOrderController
{
    use ApiResponseTrait;
    #[Inject]
    protected AuthManager $auth;

    public function lists(RequestInterface $request, ResponseInterface $response)
    {
        $startTime = $request->input('start_time', '');
        $endTime = $request->input('end_time', '');
        $status = $request->input('status', null);
        $query = TransOrder::query();
        if ($startTime) {
            $query->where('created_at', '>=', Carbon::parse($startTime));
        }
        if ($endTime) {
            $query->where('created_at', '<=', Carbon::parse($endTime));
        }
        if ($status !== null) {
            $query->where('status', $status);
        }
        $orders = $query->orderByDesc('created_at')->paginate(20);
        return [
            'data' => $orders->items(),
            'current_page' => $orders->currentPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
        ];
    }
}
