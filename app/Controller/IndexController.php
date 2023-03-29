<?php

declare(strict_types=1);
namespace App\Controller;

use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use OpenApi\Annotations as OA;
/**
 * @OA\Info(
 *     title="chatgptbridge API
 *     version="1.0.0",
 *     description="chatgpt 转发",
 *     @OA\Contact(
 *         email="support@example.com",
 *         name="API Support",
 *         url="http://www.example.com/support"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 */

#[AutoController]
class IndexController
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        return $response->raw('Hello Hyperf!');
    }
}
