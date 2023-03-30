<?php

declare(strict_types=1);
namespace App\Controller;

use App\Exception\BusinessException;
use App\Service\AuthService;
use OpenApi\Annotations as OA;
use App\Traits\ApiResponseTrait;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface;

#[Controller]
class MiniWechatController
{
    use ApiResponseTrait;
    #[Inject]
    protected AuthService $authService;
    /**
     * @OA\Post(
     *     path="/mini_wechat/login",
     *     summary="用户登录",
     *     description="使用手机号和密码进行登录",
     *     operationId="login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="请求体",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="mobile",
     *                 type="string",
     *                 description="手机号"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 description="密码"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="返回登录信息",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="access_token",
     *                 type="string",
     *                 description="访问令牌"
     *             ),
     *             @OA\Property(
     *                 property="token_type",
     *                 type="string",
     *                 description="令牌类型"
     *             ),
     *             @OA\Property(
     *                 property="expire_in",
     *                 type="integer",
     *                 description="过期时间（秒）"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="用户名或密码错误"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="请求参数验证失败"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="服务器内部错误"
     *     )
     * )
     */
    #[RequestMapping(path: 'login')]
    public function login(RequestInterface $request): ResponseInterface
    {
        try {
            $result = $this->authService->login($request->post('mobile'),$request->post('password'),'mini');
        }catch (BusinessException $exception){
            return $this->fail($exception->getMessage(),$exception->getCode());
        }
        return $this->success($result);
    }
    /**
     * @OA\Post(
     *     path="/mini_wechat/register",
     *     summary="Register a new user and login",
     *     description="Register a new user and login with the 'mini' guard",
     *     operationId="register",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=false,
     *         description="Request body",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="referrer_id",
     *                 type="integer",
     *                 description="Referrer user ID"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Returns the access token and its type",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="access_token",
     *                 type="string",
     *                 description="JWT token"
     *             ),
     *             @OA\Property(
     *                 property="token_type",
     *                 type="string",
     *                 description="Token type"
     *             ),
     *             @OA\Property(
     *                 property="expire_in",
     *                 type="integer",
     *                 description="Expiration time in seconds"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */

    #[RequestMapping(path: 'register')]
    public function register(RequestInterface $request): ResponseInterface
    {
        $referrer_id = $request->post('referrer_id');
        $result = $this->authService->registerAndLogin('mini');
        return $this->success($result);
    }
}