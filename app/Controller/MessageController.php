<?php

declare(strict_types=1);
namespace App\Controller;

use App\Exception\BusinessException;
use App\Middleware\Auth\RefreshTokenMiddleware;
use App\Service\MessageService;
use App\Traits\ApiResponseTrait;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use HyperfExtension\Auth\AuthManager;
use OpenApi\Annotations as OA;

#[Middlewares([RefreshTokenMiddleware::class])]
#[Controller]
class MessageController
{
    use ApiResponseTrait;
    #[Inject]
    protected AuthManager $auth;
    #[Inject]
    protected MessageService $messageService;
    /**
     * @OA\Post(
     *     path="/delete",
     *     summary="删除消息",
     *     description="删除指定 id 的消息",
     *     operationId="deleteMessage",
     *     tags={"Message"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="请求体",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="id",
     *                 type="integer",
     *                 description="消息 ID"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 description="成功删除消息返回 true"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="指定的消息不存在"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="服务器内部错误"
     *     )
     * )
     */
    #[RequestMapping(path: 'delete', methods: 'post')]
    public function delete(RequestInterface $request, ResponseInterface $response)
    {
        try {
            $id = $request->post('id');
            $message = Db::table('messages')->find($id);
            $result = $this->messageService->delete($message);
        }catch (ModelNotFoundException|BusinessException $exception){
            return $this->fail($exception->getMessage());
        }
        return $this->success($result);
    }
}
