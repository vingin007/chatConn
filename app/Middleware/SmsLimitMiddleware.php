<?php
namespace App\Middleware;

use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SmsLimitMiddleware implements MiddlewareInterface
{
    private $redis;

    public function __construct(RequestInterface $request, HttpResponse $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->redis = ApplicationContext::getContainer()->get(Redis::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): PsrResponseInterface
    {
        $mobile = $request->getParsedBody()['mobile'] ?? '';
        if (!empty($mobile)) {
            $key = 'sms_limit:' . date('Ymd') . ':' . $mobile;
            $count = $this->redis->incr($key);
            if ($count > 5) {
                return $this->response->json(['code' => 429, 'message' => '短信发送已超过上限']);
            }
        }else{
            return $this->response->json(['code' => 400, 'message' => '手机号不能为空!']);
        }
        return $handler->handle($request);
    }
}
