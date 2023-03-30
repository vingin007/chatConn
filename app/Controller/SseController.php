<?php

declare(strict_types=1);
namespace App\Controller;

use App\Event\TextMessageSend;
use App\Exception\BusinessException;
use App\Middleware\Auth\AdminAuthMiddleware;
use App\Middleware\Auth\RefreshTokenMiddleware;
use App\Model\Chat;
use App\Model\Message;
use App\Service\AudioService;
use App\Service\ChatRecordService;
use App\Service\FilterWordService;
use App\Service\GcsService;
use App\Service\OpenaiService;
use App\Traits\ApiResponseTrait;
use FFMpeg\Exception\InvalidArgumentException;
use FFMpeg\Exception\RuntimeException;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\RateLimit\Annotation\RateLimit;
use Hyperf\RateLimit\Exception\RateLimitException;
use HyperfExtension\Auth\Access\AuthorizesRequests;
use HyperfExtension\Auth\AuthManager;
use HyperfExtension\Auth\Exceptions\AuthorizationException;
use League\Flysystem\FilesystemException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use OpenApi\Annotations as OA;

#[Controller]
#[Middlewares([RefreshTokenMiddleware::class])]
class SseController
{
    use AuthorizesRequests;
    use ApiResponseTrait;
    #[Inject]
    protected AuthManager $auth;
    #[Inject]
    protected AudioService $audioService;
    #[Inject]
    protected EventDispatcherInterface $eventDispatcher;

    #[Inject]
    protected ChatRecordService $chatRecordService;

    /**
     * @OA\Post(
     *     path="/sse/text",
     *     summary="发送文本消息",
     *     description="在指定的聊天中发送文本消息",
     *     operationId="text",
     *     tags={"Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="请求体",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="消息内容"
     *             ),
     *             @OA\Property(
     *                 property="audio",
     *                 type="string",
     *                 format="binary",
     *                 description="上传的音频文件"
     *             ),
     *             @OA\Property(
     *                 property="chat_id",
     *                 type="integer",
     *                 description="聊天 ID"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="返回消息实体",
     *         @OA\JsonContent(ref="#/components/schemas/Message")
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
     *
     */
    #[RateLimit(create: 1,capacity: 4)]
    #[RequestMapping(path: 'text',methods: 'post')]
    public function text(RequestInterface $request, ResponseInterface $response,OpenaiService $openaiService,FilterWordService $filterWordService)
    {
        $user = $this->auth->guard('mini')->user();
        $content = $request->input('message', '');
        $file = $request->file('audio');
        $chatId = $request->input('chat_id', '');
        Db::beginTransaction();
        try {
            $chat = Chat::query()->findOrFail($chatId);
            if (!empty($file)){
                $message = $this->audioService->upload($user, $chat, $file);
            }else{
                $message = $this->chatRecordService->addChatLog($user, $chat, $content, 'text', '', '', true);
            }
            Db::commit();
        }catch (GuzzleException|ModelNotFoundException|NotFoundExceptionInterface|ContainerExceptionInterface $exception){
            Db::rollBack();
           return $this->fail($exception->getMessage());
        }catch (RateLimitException $exception) {
            Db::rollBack();
            return $this->fail('发送通道拥挤，请等一秒', 422);
        }
        return $message;
    }
    /**
     * @OA\Post(
     *     path="/sse/audio",
     *     summary="上传音频文件并发送消息",
     *     description="上传音频文件并将其转化为文本发送到指定的聊天中",
     *     operationId="audio",
     *     tags={"Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="请求体",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="chat_id",
     *                 type="integer",
     *                 description="聊天 ID"
     *             ),
     *             @OA\Property(
     *                 property="message_id",
     *                 type="integer",
     *                 description="消息 ID"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="返回 objectName",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="objectName",
     *                 type="string",
     *                 description="音频文件对象名"
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
     *         description="指定的聊天或消息不存在"
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
    #[RequestMapping(path: 'audio',methods: 'post')]
    #[RateLimit(create: 1,capacity: 4)]
    public function audio(RequestInterface $request, ResponseInterface $response)
    {
        $chatId = $request->input('chat_id', '');
        $messageId = $request->input('message_id', '');
        $user = $this->auth->guard('mini')->user();
        try {
            $chat = Chat::query()->findOrFail($chatId);
            $message = Message::query()->findOrFail($messageId);
            $objectName = $this->audioService->send($user, $chat, $message);
        } catch (ApiException|ValidationException $e) {
            return $this->fail("语音识别失败，请重试!");
        } catch (GuzzleException $e) {
            return $this->fail("GPT模型链接失败，请重试!");
        } catch (RuntimeException|InvalidArgumentException $e) {
            return $this->fail($e->getMessage());
        }catch (RateLimitException $exception) {
            return $this->fail('发送通道拥挤，请等一秒', 422);
        }
        return $this->success($objectName);
    }
    /**
     * Uploads an audio file for a chat.
     *
     * @OA\Post(
     *     path="/sse/upload_audio",
     *     summary="Uploads an audio file for a chat.",
     *     description="Uploads an audio file for a chat specified by chat ID.",
     *     operationId="uploadAudio",
     *     tags={"Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="The audio file to upload.",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"audio"},
     *                 @OA\Property(
     *                     property="audio",
     *                     description="The audio file to upload.",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *                 @OA\Property(
     *                     property="chat_id",
     *                     description="The ID of the chat the audio file belongs to.",
     *                     type="integer",
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="The uploaded audio file details.",
     *         @OA\JsonContent(ref="#/components/schemas/Message")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized. The user must be logged in to upload an audio file."
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden. The user does not have permission to upload an audio file."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found. The specified chat does not exist."
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity. The request body is missing required fields."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error. An error occurred while processing the request."
     *     )
     * )
     */
    #[RequestMapping(path: 'upload_audio',methods: 'post')]
    public function uploadAudio(RequestInterface $request)
    {
        $file = $request->file('audio');
        $chatId = $request->input('chat_id', '');
        $chat = Chat::query()->findOrFail($chatId);
        $user = $this->auth->guard('mini')->user();
        //上传到服务器
        try {
            $message = $this->audioService->upload($user, $chat, $file);
        } catch (FilesystemException|RuntimeException|InvalidArgumentException|BusinessException $e) {
            return $this->fail($e->getMessage());
        }
        return $message;
    }
    /**
     * @OA\Get(
     *     path="/sse/getaudio",
     *     operationId="getAudioFile",
     *     tags={"Message"},
     *     summary="Get audio file",
     *     description="Returns the audio file as a stream",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="filename",
     *         in="query",
     *         description="The name of the audio file to retrieve",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Audio file",
     *         @OA\MediaType(
     *             mediaType="audio/mpeg",
     *             @OA\Schema(
     *                 type="string",
     *                 format="binary"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="File not found"
     *     )
     * )
     */
    #[RequestMapping(path: 'getaudio',methods: 'get')]
    public function getaudio(RequestInterface $request, ResponseInterface $response,GcsService $gcsService)
    {
        $filename = $request->input('filename');
        $audioString = $gcsService->get($filename);
        return $this->success($audioString);
    }
    /**
     * @OA\Post(
     *     path="/sse/record",
     *     operationId="addChatRecord",
     *     summary="添加聊天记录",
     *     description="添加一条聊天记录，并返回新添加的记录信息。",
     *     tags={"Message"},
     *     security={{"bearerAuth":{}}},
     *     requestBody={"$ref": "#/components/requestBodies/AddChatRecordRequestBody"},
     *     @OA\Response(response=200, description="成功", @OA\JsonContent(ref="#/components/schemas/ChatRecord")),
     *     @OA\Response(response=400, description="请求参数错误"),
     *     @OA\Response(response=401, description="用户未认证或认证失败"),
     *     @OA\Response(response=404, description="聊天记录不存在"),
     *     @OA\Response(response=500, description="服务器内部错误")
     * )
     */
    #[RequestMapping(path: 'record',methods: 'post')]
    public function record(RequestInterface $request, ResponseInterface $response)
    {
        $message = $request->input('message');
        $chat = Chat::query()->findOrFail($request->input('chat_id'));
        $user = $this->auth->guard('mini')->user();
        $result = $this->chatRecordService->addChatLog($user, $chat, $message, 'text', '', '', false);
        $this->eventDispatcher->dispatch(new TextMessageSend($user));
        return $this->success($result);
    }
}
