<?php

declare(strict_types=1);

namespace App\Service;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Utils\ApplicationContext;
use HyperfExtension\Jwt\Jwt;
use HyperfExtension\Jwt\Token;

class JwtService
{
    private $jwt;

    public function __construct()
    {
        $this->jwt = ApplicationContext::getContainer()->get(Jwt::class);
    }

    /**
     * 生成 JWT Token.
     *
     * @param mixed $payload 载荷
     * @param int $exp 过期时间
     * @return Token|null
     */
    public function getToken($payload, int $exp = 7200): ?Token
    {
        return $this->jwt->getToken($payload, $exp);
    }

    /**
     * 验证 JWT Token.
     *
     * @param string $token JWT Token
     * @return bool|array 载荷信息
     */
    public function verifyToken(string $token)
    {
        return $this->jwt->verifyToken($token);
    }

    /**
     * 解析 JWT Token.
     *
     * @param string $token JWT Token
     * @return bool|array 载荷信息
     */
    public function decode(string $token)
    {
        return $this->jwt->decode($token);
    }

    /**
     * 刷新 JWT Token.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return Token|null
     */
    public function refreshToken(RequestInterface $request, ResponseInterface $response): ?Token
    {
        $authorizationHeader = $request->getHeader('Authorization')[0] ?? '';
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $payload = $this->decodeToken($token)['payload'] ?? [];
        return $this->getToken($payload);
    }
}
