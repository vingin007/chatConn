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
    /**
     * @OA\Get(
     *     path="/trans_order/lists",
     *     summary="Get list of user's trans orders",
     *     description="Returns paginated list of trans orders",
     *     tags={"Trans Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_time",
     *         in="query",
     *         description="Start time of creation",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="End time of creation",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Trans order status (1=pending, 2=completed)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TransOrder")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     * )
     */
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
    /**
     * @OA\Post(
     *     path="/trans_order/create",
     *     summary="Create a new transcription order",
     *     tags={"Trans Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="file_id", type="integer", description="ID of the file to transcribe")
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(ref="#/components/schemas/TransOrder")
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", description="Error message"),
     *             @OA\Property(property="code", type="integer", description="Error code")
     *         )
     *     )
     * )
     */
    #[RequestMapping(path: 'create',methods: 'post')]
    public function create(RequestInterface $request, ResponseInterface $response)
    {
        $file_id = $request->input('file_id', 0);
        try {
            $order = $this->transOrderService->generateOrder($file_id,$this->user);
        }catch (\Exception|BusinessException $e){
            return $this->fail($e->getMessage(),401);
        }
        return $this->success($order);
    }
}
