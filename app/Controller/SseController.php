<?php

declare(strict_types=1);

namespace App\Controller;

use App\Event\TextMessageSend;
use App\Exception\BusinessException;
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
use HyperfExtension\Auth\Access\AuthorizesRequests;
use HyperfExtension\Auth\AuthManager;
use HyperfExtension\Auth\Exceptions\AuthorizationException;
use League\Flysystem\FilesystemException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="API Title",
 *     version="1.0.0",
 *     description="API description"
 * )
 */

#[Controller]
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
     *     path="/text",
     *     summary="发送文本消息",
     *     description="在指定的聊天中发送文本消息",
     *     operationId="text",
     *     tags={"Message"},
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
    #[RequestMapping(path: 'text',methods: 'post')]
    #[Middlewares([RefreshTokenMiddleware::class])]
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
            $return = $openaiService->text(['role' => 'user', 'content' => $message->content],$user, $chat,3000);
            $result = $this->chatRecordService->addChatLog($user, $chat, $return, 'text', '', '', false);
            $this->eventDispatcher->dispatch(new TextMessageSend($user));
            Db::commit();
        }catch (GuzzleException|ModelNotFoundException|NotFoundExceptionInterface|ContainerExceptionInterface $exception){
            Db::rollBack();
           return $this->fail($exception->getMessage());
        }
        return $result;
    }
    /**
     * @OA\Post(
     * path="/audio",
     * summary="上传音频文件并发送消息",
     * description="上传音频文件并将其转化为文本发送到指定的聊天中",
     * operationId="audio",
     * tags={"Message"},
     * @OA\RequestBody(
     * required=true,
     * description="请求体",
     * @OA\JsonContent(
     * @OA\Property(
     * property="chat_id",
     * type="integer",
     * description="聊天 ID"
     * ),
     * @OA\Property(
     * property="message_id",
     * type="integer",
     * description="消息 ID"
     * )
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="返回 objectName",
     * @OA\JsonContent(
     * @OA\Property(
     * property="objectName",
     * type="string",
     * description="音频文件对象名"
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="用户未认证"
     * ),
     * @OA\Response(
     * response=403,
     * description="用户没有权限访问该资源"
     * ),
     * @OA\Response(
     * response=404,
     * description="指定的聊天或消息不存在"
     * ),
     * @OA\Response(
     * response=422,
     * description="请求参数验证失败"
     * ),
     * @OA\Response(
     * response=500,
     * description="服务器内部错误"
     * )
     * )
     *
     */
    #[RequestMapping(path: 'audio',methods: 'post')]
    #[Middlewares([RefreshTokenMiddleware::class])]
    public function audio(RequestInterface $request, ResponseInterface $response)
    {
        try {
            $this->authorize("update", Message::class);
        } catch (AuthorizationException $e) {
            return $this->fail($e->getMessage());
        }
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
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface|FilesystemException|RuntimeException|InvalidArgumentException $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success($objectName);
    }
    /**
     * Uploads an audio file for a chat.
     *
     * @OA\Post(
     *     path="/upload_audio",
     *     summary="Uploads an audio file for a chat.",
     *     description="Uploads an audio file for a chat specified by chat ID.",
     *     operationId="uploadAudio",
     *     tags={"Message"},
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
        try {
            $this->authorize("update", Message::class);
        } catch (AuthorizationException $e) {
            return $this->fail($e->getMessage());
        }
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
     *     path="/getaudio",
     *     operationId="getAudioFile",
     *     tags={"Message"},
     *     summary="Get audio file",
     *     description="Returns the audio file as a stream",
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
        try {
            $this->authorize("view", Message::class);
        } catch (AuthorizationException $e) {
            return $this->fail($e->getMessage());
        }
        $filename = $request->input('filename');
        $audioString = $gcsService->get($filename);
        return $this->success($audioString);
    }
}
