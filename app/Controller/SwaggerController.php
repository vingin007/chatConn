<?php

declare(strict_types=1);
namespace App\Controller;

use App\Traits\ApiResponseTrait;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use OpenApi\Generator;

#[Controller]
class SwaggerController
{
    use ApiResponseTrait;
    #[RequestMapping(path: 'index', methods: 'get')]
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $openapi = Generator::scan(['App']);

        header('Content-Type: application/x-yaml');
        return $response->raw($openapi->toYaml());
    }
}
