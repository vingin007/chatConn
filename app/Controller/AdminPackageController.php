<?php

namespace App\Controller;

use App\Middleware\Auth\AdminAuthMiddleware;
use App\Service\PackageService;
use App\Traits\ApiResponseTrait;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
#[Controller]
#[Middlewares([AdminAuthMiddleware::class])]
class AdminPackageController
{
    use ApiResponseTrait;
    #[Inject]
    private PackageService $packageService;

    public function __construct(PackageService $packageService)
    {
        $this->packageService = $packageService;
    }

    #[RequestMapping(path: 'lists',methods: 'get')]
    public function getAllPackages(RequestInterface $request)
    {
        return $this->packageService->getAll();
    }

    #[RequestMapping(path: 'create',methods: 'post')]
    public function createPackage(RequestInterface $request)
    {
        $data = $request->all();
        $package = $this->packageService->create($data);
        return $package;
    }

    #[RequestMapping(path: 'delete',methods: 'post')]
    public function deletePackage(RequestInterface $request)
    {
        $success = $this->packageService->delete($request->input('id'));
        if (!$success) {
            return $this->fail('Package not found',404);
        }
        return true;
    }
}
