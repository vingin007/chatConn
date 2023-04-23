<?php

namespace App\Controller;

use App\Middleware\Auth\AdminAuthMiddleware;
use App\Middleware\Auth\RefreshTokenMiddleware;
use App\Service\PackageService;
use App\Service\StatisticsService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use OpenApi\Annotations as OA;
#[Controller]
#[Middlewares([RefreshTokenMiddleware::class])]
class PackageController
{
    #[Inject]
    private PackageService $packageService;

    /**
     * @OA\Get(
     *     path="/package/lists",
     *     operationId="getAllPackages",
     *     tags={"Packages"},
     *     summary="Get all packages",
     *     security={{"bearerAuth":{}}},
     *     description="Returns an array of all available packages",
     *     @OA\Response(
     *         response=200,
     *         description="Array of packages",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Package")
     *         )
     *     )
     * )
     */
    #[RequestMapping(path: 'lists',methods: 'get')]
    public function getAllPackages(RequestInterface $request)
    {
        return $this->packageService->getAll();
    }

}
