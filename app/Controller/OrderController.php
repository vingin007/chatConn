<?php
namespace App\Controller;

use App\Exception\BusinessException;
use App\Model\Package;
use App\Service\OrderService;
use App\Traits\ApiResponseTrait;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use HyperfExtension\Auth\AuthManager;
#[Controller]
class OrderController extends AbstractController
{
    use ApiResponseTrait;
    #[Inject]
    protected OrderService $orderService;

    protected $user;
    #[Inject]
    protected AuthManager $auth;

    public function __construct()
    {
        $this->user = $this->auth->guard('mini')->user();
    }
    /**
     * @OA\Post(
     *     path="/order/create",
     *     tags={"Order"},
     *     summary="Create a new order",
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
    public function createOrder()
    {
        $packageId = $this->request->input('package_id');
        try {
            $package = Package::findOrFail($packageId);
            $order = $this->orderService->generateOrder($package, $this->user);
        } catch (BusinessException $e) {
            return $this->fail($e->getErrorCode(),$e->getErrorMessage());
        }

        return $this->success($order);
    }
    /**
     * @OA\Post(
     *     path="/order/cancel",
     *     tags={"Order"},
     *     summary="Cancel an existing order",
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
    public function cancelOrder()
    {
        $orderNo = $this->request->input('order_no');

        try {
            $order = $this->orderService->cancelOrder($orderNo,$this->user);
        } catch (BusinessException $e) {
            return $this->fail($e->getErrorCode(),$e->getErrorMessage());
        }

        return $this->success($order);
    }
}
