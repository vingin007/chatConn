<?php

declare(strict_types=1);
namespace App\Controller;

use Fig\Http\Message\StatusCodeInterface;
use App\Traits\ApiResponseTrait;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use HyperfExtension\Jwt\Contracts\JwtFactoryInterface;
use Psr\Http\Message\ResponseInterface;

#[Controller]
class AdminAuthController
{
    use ApiResponseTrait;

    #[RequestMapping(path: 'login',methods: 'post')]
    public function login(RequestInterface $request): ResponseInterface
    {
        $credentials = $request->inputs(['name', 'password']);
        if (!$token = auth('admin')->attempt($credentials)) {
            return $this->setHttpCode(StatusCodeInterface::STATUS_UNAUTHORIZED)->fail('Unauthorized',401);
        }
        return $this->respondWithToken($token);
    }
    #[RequestMapping(path: 'me')]
    public function me(): ResponseInterface
    {
        return $this->success(auth('admin')->user());
    }

    #[RequestMapping(path: 'logout',methods: 'post')]
    public function logout(): ResponseInterface
    {
        auth('admin')->logout();
        return $this->success(['message' => 'Successfully logged out']);
    }

    /**
     * @param $token
     * @return ResponseInterface
     */
    protected function respondWithToken($token): ResponseInterface
    {
        return $this->success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expire_in' => make(JwtFactoryInterface::class)->make()->getPayloadFactory()->getTtl()
        ]);
    }

}