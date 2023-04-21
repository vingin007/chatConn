<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\BusinessException;
use App\Middleware\Auth\RefreshTokenMiddleware;
use App\Model\TransOrder;
use App\Service\TransOrderService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
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
    #[Inject]
    protected TransOrderService $transOrderService;
    public function __construct()
    {
        $this->user = $this->auth->guard('mini')->user();
    }
    #[RequestMapping(path: 'lists',methods: 'get')]
    public function lists(RequestInterface $request, ResponseInterface $response)
    {
        $startTime = $request->input('start_time', '');
        $endTime = $request->input('end_time', '');
        $status = $request->input('status', null);
        $query = TransOrder::query()->where('user_id', $this->user->id);
        if ($startTime) {
            $query->where('created_at', '>=', Carbon::parse($startTime,'Asia/Shanghai'));
        }
        if ($endTime) {
            $query->where('created_at', '<=', Carbon::parse($endTime,'Asia/Shanghai'));
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
    #[RequestMapping(path: 'create',methods: 'post')]
    public function create(RequestInterface $request, ResponseInterface $response)
    {
        $file_id = $request->input('file_id', 0);
        if($this->user->level != 99){
            return $this->fail('您没有开通该功能',401);
        }
        try {
            $order = $this->transOrderService->generateOrder($file_id,$this->user);
        }catch (\Exception|BusinessException $e){
            return $this->fail($e->getMessage(),401);
        }
        return $this->success($order);
    }
}
