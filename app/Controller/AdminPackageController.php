<?php

namespace App\Controller;

use App\Middleware\Auth\AdminAuthMiddleware;
use App\Service\PackageService;
use App\Service\StatisticsService;
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

    #[RequestMapping(path: 'lists',methods: 'get')]
    public function getAllPackages(RequestInterface $request)
    {
        return $this->packageService->getAll();
    }
    /**
     * @OA\Post(
     *     path="/admin_package/create",
     *     summary="创建套餐",
     *     tags={"Package"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="请求体",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 description="套餐名称",
     *             ),
     *             @OA\Property(
     *                 property="quota",
     *                 type="integer",
     *                 description="发言条数",
     *             ),
     *             @OA\Property(
     *                 property="duration",
     *                 type="integer",
     *                 description="有效时长（天）",
     *             ),
     *             @OA\Property(
     *                 property="level",
     *                 type="integer",
     *                 description="等级（1-5）",
     *             ),
     *             @OA\Property(
     *                 property="price",
     *                 type="number",
     *                 format="float",
     *                 description="价格",
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="创建成功",
     *         @OA\JsonContent(ref="#/components/schemas/Package")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="请求参数不正确",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="错误信息",
     *             ),
     *         ),
     *     ),
     * )
     */

    #[RequestMapping(path: 'create',methods: 'post')]
    public function createPackage(RequestInterface $request)
    {
        $data = $request->all();
        $package = $this->packageService->create($data);
        return $package;
    }
    /**
     * @OA\Post (
     *     path="/admin_package/delete",
     *     summary="删除套餐",
     *     tags={"Package"},
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="套餐 ID",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="删除成功",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 description="是否删除成功",
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="套餐不存在",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="错误信息",
     *             ),
     *         ),
     *     ),
     * )
     */

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
