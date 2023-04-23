<?php

namespace App\Controller;

use App\Exception\BusinessException;
use App\Service\ApiKeyService;
use App\Traits\ApiResponseTrait;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use HyperfExtension\Auth\AuthManager;
use Psr\Http\Message\RequestInterface;
use OpenApi\Annotations as OA;

#[Controller]
class ApiKeyController extends AbstractController
{
    use ApiResponseTrait;
    #[Inject]
    protected AuthManager $auth;
    protected $user;
    #[Inject]
    protected ApiKeyService $apiKeyService;

    public function __construct()
    {
        $this->user = $this->auth->guard('mini')->user();
    }
    /**
     * @OA\Post(
     *     path="/api_key/bind",
     *     summary="Bind API key to user",
     *     tags={"API Key"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"apikey"},
     *                 @OA\Property(
     *                     property="apikey",
     *                     type="string",
     *                     description="API key to bind to user"
     *                 ),
     *                 example={"apikey": "api_key_12345"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="API key successfully bound to user",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="User does not meet conditions to bind API key",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="尚未达到绑定api_key的条件"
     *                 ),
     *                 @OA\Property(
     *                     property="code",
     *                     type="integer",
     *                     example=401
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    #[RequestMapping(path: 'bind',methods: 'POST')]
    public function bind(RequestInterface $request)
    {
        $apikey = $request->post('apikey');
        try {
            $result = $this->apiKeyService->bind($apikey, $this->user);
        } catch (BusinessException $e) {
            return $this->fail($e->getMessage(), $e->getErrorCode());
        }
        return $this->success($result);
    }
    /**
     * @OA\Post(
     *     path="/api_key/remove",
     *     summary="移除用户 API Key",
     *     tags={"API Key"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Response(response="200", description="移除成功", @OA\JsonContent(example={"success":true})),
     *     @OA\Response(response="401", description="尚未绑定 API Key", @OA\JsonContent(example={"success":false, "message":"尚未绑定 API Key"}))
     * )
     */

    #[RequestMapping(path: 'remove',methods: 'POST')]
    public function remove(RequestInterface $request)
    {
        try {
            $result = $this->apiKeyService->remove($this->user);
        } catch (BusinessException $e) {
            return $this->fail($e->getMessage(), $e->geterrorCode());
        }
        return $this->success($result);
    }
}
