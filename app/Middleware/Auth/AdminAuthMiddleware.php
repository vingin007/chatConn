<?php

declare(strict_types=1);

namespace App\Middleware\Auth;

use App\Traits\ApiResponseTrait;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Hyperf\Di\Annotation\Inject;
use HyperfExtension\Auth\AuthManager;
use HyperfExtension\Jwt\Exceptions\TokenExpiredException;
use HyperfExtension\Jwt\JwtFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AdminAuthMiddleware implements MiddlewareInterface
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
            $user = $this->authManager->guard('admin')->getProvider()->retrieveByCredentials(['id' => $jwt->getClaim('sub')]);
            if ($user) {
                $this->authManager->guard('admin')->setUser($user);
            }
        } catch (Exception $exception) {
            if (! $exception instanceof TokenExpiredException) {
                return $this->setHttpCode(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY)->fail($exception->getMessage());
            }
        }

        return $handler->handle($request);
    }
}