<?php
namespace App\Middleware;

use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Response;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SmsIpLimitMiddleware implements MiddlewareInterface
{
    private $redis;

    public function __construct()
    {
        $this->redis = ApplicationContext::getContainer()->get(Redis::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): PsrResponseInterface
    {
        $ip = $request->getServerParams()['remote_addr'] ?? '';
        if (!empty($ip)) {
            $key = 'sms_limit:' . date('Ymd') . ':' . $ip;
            $count = $this->redis->incr($key);
            if ($count > 5) {
                $response = new Response();
                $response->withBody(new SwooleStream('Exceed daily sms limit.'));
                return $response;
            }
        }
        return $handler->handle($request);
    }
}
