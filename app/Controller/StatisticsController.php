<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\Auth\AdminAuthMiddleware;
use App\Service\StatisticsService;
use App\Traits\ApiResponseTrait;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

#[Middlewares([AdminAuthMiddleware::class])]
#[Controller]
class StatisticsController
{
    use ApiResponseTrait;
    #[Inject]
    private StatisticsService $service;
    #[RequestMapping(path: 'user7days', methods: 'get')]
    public function user7daysCount()
    {
        return $this->success($this->service->user7daysCount());
    }
    #[RequestMapping(path: 'days', methods: 'get')]
    public function getLastSevenDays(): array
    {
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = date('m-d', strtotime("-$i day"));
        }
        return $dates;
    }
    #[RequestMapping(path: 'order7days', methods: 'get')]
    public function order7daysCount()
    {
        return $this->success($this->service->order7daysCount());
    }
    #[RequestMapping(path: 'gpt7days', methods: 'get')]
    public function gpt7daysCount()
    {
        return $this->success($this->service->gpt7daysCount());
    }
    #[RequestMapping(path: 'users', methods: 'get')]
    public function users()
    {
        return $this->success($this->service->getAllUserStatistics());
    }
    #[RequestMapping(path: 'orders', methods: 'get')]
    public function orders()
    {
        return $this->success($this->service->getAllOrderStatistics()) ;
    }
    #[RequestMapping(path: 'gpts', methods: 'get')]
    public function gpts()
    {
        return $this->success($this->service->getAllMessageStatistics());
    }
}
