<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\Auth\AdminAuthMiddleware;
use App\Traits\ApiResponseTrait;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller]
#[Middlewares([AdminAuthMiddleware::class])]
class AdminPaycodeController
{
    use ApiResponseTrait;
    #[RequestMapping(path: 'upload',methods: 'post')]
    public function upload(RequestInterface $request)
    {
        $file = $request->file('file');

        // 保存文件到指定目录，文件名为 paycode.jpg
        $filename = 'paycode.jpg';
        $path = BASE_PATH . '/storage/paycode/' . $filename;
        $file->moveTo($path);

        return $this->success(['url' => '/storage/paycode/' . $filename]);
    }
}
