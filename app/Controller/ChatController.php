<?php

declare(strict_types=1);
namespace App\Controller;

use App\Middleware\Auth\AdminAuthMiddleware;
use App\Middleware\Auth\RefreshTokenMiddleware;
use App\Model\Chat;
use App\Service\ChatService;
use App\Traits\ApiResponseTrait;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use HyperfExtension\Auth\AuthManager;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

/**
 * @OA\Info(
 *     title="My API",
 *     version="1.0.0",
 *     description="API for My Application"
 * )
 */

/**
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * )
 */

#[Middlewares([
    RefreshTokenMiddleware::class,
])]
#[Controller]
class ChatController
{
    use ApiResponseTrait;
    #[Inject]
    protected AuthManager $auth;
    protected $user;
    public function __construct()
    {
        $this->user = $this->auth->guard('mini')->user();
    }
    /**
     * 创建聊天
     *
     * @OA\Post(
     *     path="/chat/create",
     *     tags={"Chat"},
     *     summary="创建聊天",
     *     description="创建一个新的聊天。",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="成功", @OA\JsonContent(ref="#/components/schemas/Chat")),
     *     @OA\Response(response="401", description="未授权"),
     *     @OA\Response(response="422", description="验证失败")
     * )
     */
    #[RequestMapping(path: 'create', methods: 'post')]
    public function create(RequestInterface $request, ChatService $chatService)
    {
        $chat = $chatService->createChat($this->user);
        return $this->success($chat);
    }
    /**
     * @OA\Post(
     *     path="/chat/delete",
     *     summary="删除聊天",
     *     description="删除指定的聊天",
     *     operationId="deleteChat",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="请求体",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="chat_id",
     *                 type="integer",
     *                 description="聊天 ID"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="删除成功",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 description="是否删除成功"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="用户未认证"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="用户没有权限访问该资源"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="指定的聊天不存在"
     *     )
     * )
     */

    #[RequestMapping(path: 'delete', methods: 'post')]
    public function delete(RequestInterface $request, ChatService $chatService)
    {
        $chatId = $request->post('chat_id');
        $chat = Chat::find($chatId);
        if (!$chat || $chat->user_id != $this->user->id) {
            return $this->fail('删除失败');
        }
        $result = $chatService->deleteChat($this->user, $chat);
        if (!$result) {
            return $this->fail('删除失败',422);
        }
        return $this->success(true);
    }
    /**
     * @OA\Post(
     *     path="/chat/rename",
     *     summary="修改聊天分组名称",
     *     description="修改指定聊天分组的名称",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="请求体",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="chat_id",
     *                 type="integer",
     *                 description="聊天分组 ID"
     *             ),
     *             @OA\Property(
     *                 property="new_name",
     *                 type="string",
     *                 description="新的聊天分组名称"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="修改成功",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="boolean",
     *                 description="修改成功"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未认证的请求"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="无权访问的资源"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="聊天分组不存在"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="请求参数验证失败"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="服务器内部错误"
     *     )
     * )
     */

    #[RequestMapping(path: 'rename', methods: 'post')]
    public function rename(RequestInterface $request, ChatService $chatService)
    {
        $chatId = $request->post('chat_id');
        $newName = $request->post('new_name');
        $result = $chatService->renameChat($this->user, $chatId, $newName);
        if (!$result) {
            return $this->fail('重命名失败',422);
        }
        return $this->success(true);
    }
    /**
     * @OA\Get(
     *     path="/chat/lists",
     *     summary="获取聊天列表",
     *     description="获取当前用户的所有聊天列表",
     *     operationId="chatList",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response="200",
     *         description="返回聊天列表",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", description="聊天ID"),
     *                 @OA\Property(property="name", type="string", description="聊天名称"),
     *                 @OA\Property(property="type", type="integer", description="聊天类型")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="用户未认证"
     *     )
     * )
     */
    #[RequestMapping(path: 'lists', methods: 'get')]
    public function list(RequestInterface $request, ChatService $chatService): ResponseInterface
    {
        $chats = $chatService->getChat($this->user);
        return $this->success($chats);
    }
}
