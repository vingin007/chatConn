<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

#[AutoController]
class IndexController
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {

    }
}
