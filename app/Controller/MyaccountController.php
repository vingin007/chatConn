<?php

declare(strict_types=1);
namespace App\Controller;

use App\Exception\BusinessException;
use App\Middleware\Auth\AdminAuthMiddleware;
use App\Middleware\Auth\RefreshTokenMiddleware;
use App\Middleware\SmsIpLimitMiddleware;
use App\Middleware\SmsLimitMiddleware;
use App\Model\User;
use App\Service\AuthService;
use App\Service\SmsService;
use App\Service\UserService;
use App\Traits\ApiResponseTrait;
use App\Utils\PhoneRule;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use OpenApi\Annotations as OA;

#[Controller]
#[Middlewares([RefreshTokenMiddleware::class])]
class MyaccountController
{
    use ApiResponseTrait;
    #[Inject]
    protected UserService $userService;
    #[Inject]
    protected SmsService $smsService;
    #[Inject]
    protected AuthService $authService;
    #[Inject]
    protected ValidatorFactoryInterface $validatorFactory;
    /**
     * @OA\Post(
     *     path="/myaccount/bind_mobile",
     *     summary="绑定手机",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="请求体",
     *         @OA\JsonContent(
     *             required={"mobile", "code", "password", "password_confirmation"},
     *             @OA\Property(property="mobile", type="string", format="mobile", description="手机号"),
     *             @OA\Property(property="code", type="string", description="短信验证码"),
     *             @OA\Property(property="password", type="string", description="密码"),
     *             @OA\Property(property="password_confirmation", type="string", description="确认密码")
     *         )
     *     ),
     *     @OA\Response(response="200", description="绑定成功"),
     *     @OA\Response(response="401", description="用户未认证"),
     *     @OA\Response(response="422", description="请求参数验证失败"),
     *     @OA\Response(response="500", description="服务器内部错误")
     * )
     */
    #[RequestMapping(path: 'bind_mobile')]
    public function bindMobile(RequestInterface $request,ResponseInterface $response)
    {
        $mobile = $request->post('mobile');
        $code = $request->post('code');
        $user = $this->authService->getUser('mini');
        $validator = $this->validatorFactory->make(
            $request->all(),
            [
                'mobile' => ['required', new PhoneRule(), 'unique:users,mobile'],
                'code' => 'required|string',
                'password' => 'required|min:6|confirmed',
            ],
            [
                'code.required' => '请输入验证码.',
                'mobile.required' => '手机号必须填写',
                'mobile.unique' => '该手机号已经存在，请使用其他手机号',
                'password.required' => '密码必须填写',
                'password.min' => '密码最少为6位',
                'password.confirmed' => '两次输入的密码不一致',
            ]
        );
        if ($validator->fails()) {
            return $response->json($validator->errors());
        }
        try {
            $result = $this->userService->bindMobile($user,$mobile,$code);
        }catch (BusinessException $e){
            return $this->fail($e->getMessage(),$e->getErrorCode());
        }
        return $this->success($result);
    }
    /**
     * Sends a verification code to the specified mobile number.
     *
     * @OA\Post(
     *     path="/myaccount/send_code",
     *     summary="Sends a verification code to the specified mobile number.",
     *     tags={"Verification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Request body.",
     *         @OA\JsonContent(
     *             required={"mobile"},
     *             @OA\Property(
     *                 property="mobile",
     *                 type="string",
     *                 example="12345678901",
     *                 description="The mobile number to send the verification code to."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success response.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true,
     *                 description="Indicates whether the verification code was sent successfully."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The mobile number is invalid.",
     *                 description="The error message."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="An unexpected error occurred.",
     *                 description="The error message."
     *             )
     *         )
     *     )
     * )
     */
    #[RequestMapping(path: 'send_code')]
    #[Middlewares([SmsLimitMiddleware::class,SmsIpLimitMiddleware::class])]
    public function sendVerificationCode(RequestInterface $request)
    {
        $mobile = $request->post('mobile');
        try {
            $this->smsService->sendVerificationCode($mobile);
        }catch (NoGatewayAvailableException $e){
            return $this->fail($e->getMessage(),401);
        }
        return true;
    }
    /**
     * @OA\Get(
     *     path="/myaccount/get_quota",
     *     summary="获取用户配额",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response="200",
     *         description="成功获取用户配额",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="quota",
     *                 type="integer",
     *                 description="用户配额"
     *             ),
     *             example={
     *                 "quota": 10
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="请求错误"
     *     ),
     * )
     */
    #[RequestMapping(path: 'get_quota',methods: 'get')]
    public function getQuota(RequestInterface $request)
    {
        $user = $this->authService->getUser('mini');
        $quota = User::query()->where('id',$user->id)->value('quota');
        return $this->success(['quota' => $quota]);
    }
}
