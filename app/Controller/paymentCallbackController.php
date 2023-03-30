<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\OrderService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
#[Controller]
class paymentCallbackController
{
    #[Inject]
    protected OrderService $orderService;
    #[RequestMapping(path: '/callback', methods: 'post')]
    public function callback(RequestInterface $request, ResponseInterface $response)
    {
        $this->orderService->payOrder($request->input('amount'));
    }
}
