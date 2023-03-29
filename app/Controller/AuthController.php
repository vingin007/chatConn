<?php

declare(strict_types=1);
namespace App\Controller;

use Fig\Http\Message\StatusCodeInterface;
use App\Traits\ApiResponseTrait;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use App\Middleware\Auth\RefreshTokenMiddleware;
use HyperfExtension\Jwt\Contracts\JwtFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @Controller(prefix="auth")
 * Class AuthController
 * @package App\Controller
 */
class AuthController
{
    use ApiResponseTrait;

    /**
     * @RequestMapping(path="login", methods={"POST"})
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function login(RequestInterface $request): ResponseInterface
    {
        $credentials = $request->inputs(['email', 'password']);
        if (!$token = auth('api')->attempt($credentials)) {
            return $this->setHttpCode(StatusCodeInterface::STATUS_UNAUTHORIZED)->fail('Unauthorized');
        }
        return $this->respondWithToken($token);
    }

    /**
     * @RequestMapping(path="user")
     * @Middlewares({@Middleware(RefreshTokenMiddleware::class)})
     */
    public function me(): ResponseInterface
    {
        return $this->success(auth('api')->user());
    }

    /**
     * @RequestMapping(path="refresh", methods={"GET"})
     */
    public function refresh(): ResponseInterface
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * @RequestMapping(path="logout", methods={"DELETE"})
     */
    public function logout(): ResponseInterface
    {
        auth('api')->logout();
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