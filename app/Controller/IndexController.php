<?php

declare(strict_types=1);
namespace App\Controller;

use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use function Hyperf\ViewEngine\view;
#[AutoController]
class IndexController
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        return view('email_verification',['code' => '3332211']);
    }
}
