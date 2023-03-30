<?php

namespace App\Controller;

use App\Middleware\Auth\AdminAuthMiddleware;
use App\Service\PackageService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\RequestMapping;
use OpenApi\Annotations as OA;
#[Controller]
#[Middleware(AdminAuthMiddleware::class)]
class PackageController
{
    #[Inject]
    private PackageService $packageService;

    public function __construct(PackageService $packageService)
    {
        $this->packageService = $packageService;
    }
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
    public function getAllPackages()
    {
        return $this->packageService->getAll();
    }

}
