<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Package;
use App\Service\OrderService;
use App\Traits\ApiResponseTrait;
use Exception;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use HyperfExtension\Auth\AuthManager;
use OpenApi\Annotations as OA;
#[Controller]
class OrderController
{
    use ApiResponseTrait;
    #[Inject]
    protected AuthManager $auth;
    protected $user;
    #[Inject]
    protected OrderService $orderService;
    public function __construct()
    {
        $this->user = $this->auth->guard('mini')->user();
    }

    /**
     * 创建订单。
     *
     *
     * @OA\Post(
     *     path="/order/create",
     *     tags={"Order"},
     *     summary="创建订单",
     *     description="用户通过购买套餐创建订单",
     *     @OA\RequestBody(
     *         required=true,
     *         description="请求体参数",
     *         @OA\JsonContent(
     *             @OA\Property(property="package_id", type="integer", description="套餐ID"),
     *         )
     *     ),
     *     @OA\Response(response="200", description="成功",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/Order"
     *         )
     *     ),
     *     @OA\Response(response="400", description="无效参数",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="参数错误")
     *         )
     *     ),
     *     @OA\Response(response="401", description="未登录",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="请先登录")
     *         )
     *     ),
     *     @OA\Response(response="403", description="无权限",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=403),
     *             @OA\Property(property="message", type="string", example="无权操作")
     *         )
     *     )
     * )
     */
    #[RequestMapping(path: 'create', methods: 'post')]
    public function create(RequestInterface $request)
    {
        $package_id = $request->input('package_id');
        try {
            $package = Package::findOrFail($package_id);
            $order = $this->orderService->getRecentOrders(41,0);
        }catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success($order);
    }
}
