<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\OrderService;
use App\Service\PaymentService;
use App\Traits\ApiResponseTrait;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use function Hyperf\ViewEngine\view;

#[Controller]
class PaymentCallbackController
{
    use ApiResponseTrait;
    #[Inject]
    protected OrderService $orderService;
    protected LoggerInterface $logger;
    #[Inject]
    protected PaymentService $paymentService;

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
    #[RequestMapping(path: 'notify', methods: 'get,post')]
    public function notify(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();

        // 验证签名
        // 假设你已经在 PaymentService 中实现了签名验证方法：`verifySign`
         $verified = $this->paymentService->verifySign($params);

        if ($verified) {
            // 更新订单状态等业务逻辑
            $this->orderService->_payOrder($params['out_trade_no'],$params['trade_no'],$params['type'],$params['money']);
            // 返回success表示接收成功
            return $response->raw('success');
        } else {
            // 签名验证失败，返回错误信息
            return $response->raw('invalid sign');
        }
    }
    #[RequestMapping(path: 'return',methods: 'post,get')]
    public function return(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();

        // 验证签名
        // 假设你已经在 PaymentService 中实现了签名验证方法：`verifySign`
        $verified = $this->paymentService->verifySign($params);

        if ($verified) {
            // 更新订单状态等业务逻辑
            $order = $this->orderService->_payOrder($params['out_trade_no'],$params['trade_no'],$params['type'],$params['money']);
            // 返回success表示接收成功
            return view('return',['status' => $order->status]);
        } else {
            // 签名验证失败，返回错误信息
            return $response->raw('invalid sign');
        }

    }
}
