<?php

declare(strict_types=1);

namespace App\Middleware\Auth;

use App\Traits\ApiResponseTrait;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Hyperf\Di\Annotation\Inject;
use HyperfExtension\Auth\AuthManager;
use HyperfExtension\Jwt\Contracts\ManagerInterface;
use HyperfExtension\Jwt\Exceptions\TokenExpiredException;
use HyperfExtension\Jwt\JwtFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RefreshTokenMiddleware implements MiddlewareInterface
{
    use ApiResponseTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    #[Inject]
    private AuthManager $authManager;


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $jwt = make(JwtFactory::class)->make();

        try {
            $token = $jwt->getToken();
            $user = $this->authManager->guard('mini')->getProvider()->retrieveByCredentials(['id' => $jwt->getClaim('sub')]);
            if ($user) {
                $this->authManager->guard('mini')->setUser($user);
            }
        } catch (Exception $exception) {
            if (! $exception instanceof TokenExpiredException) {
                return $this->setHttpCode(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY)->fail($exception->getMessage());
            }
            try {
                $token = $jwt->getToken();

                // 刷新token
                $new_token = $jwt->getManager()->refresh($token);

                // 解析token载荷信息
                $payload = $jwt->getManager()->decode($token, false, true);

                // 旧token加入黑名单
                $jwt->getManager()->getBlacklist()->add($payload);

                // 一次性登录，保证此次请求畅通
                auth($payload->get('guard') ?? 'mini')->onceUsingId($payload->get('sub'));

                return $handler->handle($request)->withHeader('authorization', 'bearer ' . $new_token);
            } catch (Exception $exception) {
                return $this->setHttpCode(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY)->fail($exception->getMessage());
            }
        }

        return $handler->handle($request);
    }
}