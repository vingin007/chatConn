<?php

declare(strict_types=1);
namespace App\Controller;

use App\Exception\BusinessException;
use App\Middleware\Auth\RefreshTokenMiddleware;
use App\Model\Message;
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
use Hyperf\Paginator\Paginator;
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
     *     path="/message/delete",
     *     summary="删除消息",
     *     description="删除指定 id 的消息",
     *     operationId="deleteMessage",
     *     tags={"Message"},
     *     security={{"bearerAuth":{}}},
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
            return $this->fail($exception->getMessage(),404);
        }
        return $this->success($result);
    }
    /**
     * @OA\Get(
     *     path="/message/lists",
     *     summary="获取消息列表",
     *     tags={"Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="页码",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="每页条数",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="返回消息列表",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="current_page",
     *                 type="integer",
     *                 description="当前页码"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="消息列表",
     *                 @OA\Items(ref="#/components/schemas/Message")
     *             ),
     *             @OA\Property(
     *                 property="first_page_url",
     *                 type="string",
     *                 description="第一页的URL链接"
     *             ),
     *             @OA\Property(
     *                 property="from",
     *                 type="integer",
     *                 description="从第几条消息开始"
     *             ),
     *             @OA\Property(
     *                 property="next_page_url",
     *                 type="string",
     *                 description="下一页的URL链接"
     *             ),
     *             @OA\Property(
     *                 property="path",
     *                 type="string",
     *                 description="当前请求的URL路径"
     *             ),
     *             @OA\Property(
     *                 property="per_page",
     *                 type="integer",
     *                 description="每页的消息数"
     *             ),
     *             @OA\Property(
     *                 property="prev_page_url",
     *                 type="string",
     *                 description="上一页的URL链接"
     *             ),
     *             @OA\Property(
     *                 property="to",
     *                 type="integer",
     *                 description="到第几条消息结束"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="服务器内部错误"
     *     )
     * )
     */
    #[RequestMapping(path: 'lists', methods: 'get')]
    public function lists(RequestInterface $request)
    {
        $currentPage = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);
        $message = Message::query()->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $currentPage);
        return $this->success($message);
    }
}
