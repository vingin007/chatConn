<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\TransOrderService;
use App\Traits\ApiResponseTrait;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

#[Controller]
class PaymentCallbackController
{
    use ApiResponseTrait;
    #[Inject]
    protected TransOrderService $orderService;
    protected LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        // 第一个参数对应日志的 name, 第二个参数对应 config/autoload/logger.php 内的 key
        $this->logger = $loggerFactory->get('log', 'default');
    }
    #[RequestMapping(path: 'callback', methods: 'get,post')]
    public function callback(RequestInterface $request, ResponseInterface $response)
    {
        $this->logger->info(json_encode($request->input('text')));
        $text = $request->post('text');
        $pattern = '/(?<=到账)\d+\.\d{2}(?=元)/';

        if (preg_match($pattern, $text, $matches)) {
            $amount = $matches[0];
        } else {
            echo "No match";
            return $this->fail('fail');
        }
        $this->orderService->payOrder($amount,$request->post('sign'),$request->post('timestamp'));
        return $this->success('success');
    }
}
