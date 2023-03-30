<?php

declare(strict_types=1);
namespace App\Controller;

use App\Service\OpenaiService;
use App\Traits\ApiResponseTrait;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use OpenApi\Annotations as OA;
#[Controller]
class ImageController
{
    use ApiResponseTrait;
    #[Inject]
    protected OpenaiService $openaiService;
    /**
     * @OA\Post(
     *     path="/image/generate",
     *     summary="生成一张图片",
     *     tags={"Image"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         description="请求体",
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="prompt",
     *                 type="string",
     *                 description="图片生成的文本提示"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="成功",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="url",
     *                 type="string",
     *                 description="生成的图片 URL"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="无效的请求参数"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="用户未认证"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="服务器内部错误"
     *     )
     * )
     */

    #[RequestMapping(path: 'generate')]
    public function generate_image(RequestInterface $request, ResponseInterface $response)
    {
        $prompt = $request->input('prompt');
        $url = $this->openaiService->generate_image($prompt,1024);
        return $this->success($url);
    }
    /**
     * 编辑图片
     *
     * @OA\Post(
     *     path="/image/edit",
     *     summary="编辑图片",
     *     description="编辑指定的原始图片并返回结果",
     *     operationId="edit_image",
     *     security={{"bearerAuth":{}}},
     *     tags={"Image"},
     *     @OA\RequestBody(
     *         description="请求体",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="original",
     *                     type="file",
     *                     description="原始图片"
     *                 ),
     *                 @OA\Property(
     *                     property="mask",
     *                     type="file",
     *                     description="要应用于原始图像的遮罩"
     *                 ),
     *                 @OA\Property(
     *                     property="prompt",
     *                     type="string",
     *                     description="提供给 OpenAI 的文本提示"
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="返回编辑后的图片 URL",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="url",
     *                 type="string",
     *                 description="编辑后的图片 URL",
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="请求参数格式不正确",
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="用户未认证",
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="用户没有权限访问该资源",
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="请求参数验证失败",
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="服务器内部错误",
     *     ),
     * )
     */

    #[RequestMapping(path: 'edit')]
    public function edit_image(RequestInterface $request, ResponseInterface $response)
    {
        $original = $request->file('original');
        $mask = $request->file('mask');
        $prompt = $request->input('prompt');
        $url = $this->openaiService->edit_image($original,$mask,$prompt,1024);
        return $this->success($url);
    }
    /**
     * @OA\Post(
     *     path="/image/variation_image",
     *     summary="变异图片",
     *     description="将原始图片通过 OpenAI 的 DALL·E 模型进行变异",
     *     security={{"bearerAuth":{}}},
     *     tags={"Image"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="上传原始图片",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="original",
     *                     type="string",
     *                     format="binary",
     *                     description="原始图片"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="返回变异后的图片地址",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="url",
     *                 type="string",
     *                 description="变异后的图片地址"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="上传文件验证失败"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="服务器内部错误"
     *     )
     * )
     */

    #[RequestMapping(path: 'variate')]
    public function variation_image(RequestInterface $request, ResponseInterface $response)
    {
        $original = $request->file('original');
        $url = $this->openaiService->variation_image($original,1024);
        return $this->success($url);
    }
}
