<?php
namespace App\Controller;

use App\Exception\BusinessException;
use App\Middleware\Auth\RefreshTokenMiddleware;
use App\Model\Order;
use App\Model\Package;
use App\Model\PaymentRecord;
use App\Service\OrderService;
use App\Service\PaymentService;
use App\Service\TransOrderService;
use App\Traits\ApiResponseTrait;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use HyperfExtension\Auth\AuthManager;
#[Controller]
#[Middlewares([RefreshTokenMiddleware::class])]
class OrderController extends AbstractController
{
    use ApiResponseTrait;
    #[Inject]
    protected OrderService $orderService;

    protected $user;
    #[Inject]
    protected AuthManager $auth;
    #[Inject]
    protected PaymentService $paymentService;

    public function __construct()
    {
        $this->user = $this->auth->guard('mini')->user();
    }
    /**
     * @OA\Post(
     *     path="/order/create",
     *     tags={"Order"},
     *     summary="Create a new order",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             required={"package_id"},
     *             @OA\Property(property="package_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Order")
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid input",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"code", "message"},
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    #[RequestMapping(path: 'create', methods: 'post')]
    public function createOrder(RequestInterface $request)
    {
        $packageId = $request->input('package_id');
        try {
            $package = Package::findOrFail($packageId);
            $order = $this->orderService->generateOrder($package, $this->user);
        } catch (BusinessException|ModelNotFoundException $e) {
            return $this->fail($e->getMessage(),400);
        }

        return $this->success($order);
    }
    /**
     * @OA\Post(
     *     path="/order/cancel",
     *     tags={"Order"},
     *     summary="Cancel an existing order",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             required={"order_no"},
     *             @OA\Property(property="order_no", type="string", example="ORDER20220324162102-5411")
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Order")
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid input",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"code", "message"},
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    #[RequestMapping(path: 'cancel', methods: 'post')]
    public function cancelOrder(RequestInterface $request)
    {
        $orderNo = $request->input('order_no');

        try {
            $order = $this->orderService->cancelOrder($orderNo,$this->user);
        } catch (BusinessException|ModelNotFoundException $e) {
            return $this->fail($e->getMessage(),400);
        }

        return $this->success($order);
    }

    public function pay(RequestInterface $request)
    {
        $orderNo = $request->input('order_no');
        $type = $request->input('type');
        try {
            $order = Order::query()->where('order_no',$orderNo)->firstOrFail();
            if ($order->status !== Order::STATUS_UNPAID) {
                throw new BusinessException('订单状态不正确');
            }
            $params = [
                'type' => $type,
                'out_trade_no' => $orderNo,
                'notify_url' => 'http://api.talksmart.cc/payment_callback/notify',
                'name' => $order->package_name,
                'money' => $order->amount,
                'clientip' => $request->getServerParams()['remote_addr'] ?? null
            ];
            $order = $this->paymentService->createPayment($params);
        } catch (BusinessException|ModelNotFoundException $e) {
            return $this->fail($e->getMessage(),400);
        }
        return $this->success($order);
    }
}
