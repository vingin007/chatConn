<?php

namespace App\Server;

use App\Event\TransOrderPaid;
use App\Model\Chat;
use App\Model\Message;
use App\Service\ChatRecordService;
use App\Service\OpenaiService;
use Hhxsv5\SSE\Event;
use Hhxsv5\SSE\SSESwoole;
use Hhxsv5\SSE\StopSSEException;
use Hyperf\Context\Context;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Event\EventDispatcher;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use HyperfExtension\Auth\AuthManager;
use HyperfExtension\Jwt\JwtFactory;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use openapi\Annotations as OA;
class EventStreamServer
{
    private AuthManager $authManager;

    #[Inject]
    private ChatRecordService $chatRecordService;
    #[Inject]
    private EventDispatcher $eventDispatcher;

    public function __construct()
    {
        $container = ApplicationContext::getContainer();
        $this->authManager = new AuthManager($container);
    }
    /**
     * @OA\Get(
     *     path="/event_stream",
     *     summary="接受消息长链接"
     *     description="消息发送成功后，用消息id请求这个接口，获取机器人回复，回复以event stream的形式返回，前端需要监听这个接口，收到event为done的时候关闭长链接",
     *     tags={"Message"},
     *     description="Your description",
     * )
     */
    public function OnRequest(Request $request, Response $response){
        $openaiService = new OpenaiService();
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Content-Type', 'text/event-stream');
        $response->header('Cache-Control', 'no-cache');
        $response->header('Connection', 'keep-alive');
        $response->header('X-Accel-Buffering', 'no');
        $params = $request->get;
        $access_token = $params['access_token'];
        $message = Message::query()->findOrFail($params['message_id']);
        $chat = Chat::query()->findOrFail($message->chat_id);
        // 将 Swoole 请求转换为 PSR-7 请求
        $psr7Request = $this->convertRequest($request);
        Context::set(ServerRequestInterface::class, $psr7Request);
        $jwt = make(JwtFactory::class)->make();
        $user = $this->authManager->guard('mini')->getProvider()->retrieveByCredentials(['id' => $jwt->getClaim('sub')]);
        $text = $openaiService->text(['role' => 'user', 'content' => $message->content],$params['message_id'],$user, $chat,3000);
        $event = new Event(function () use ($params) {
            //redis fetch data
            $redis = di()->get(Redis::class);
            $chunk = $redis->lPop('message_answer:'.$params['message_id']);
            if (empty($chunk)) {
                return false; // Return false if no new messages
            }
            $shouldStop = false; // Stop if something happens or to clear connection, browser will retry
            if ($shouldStop) {
                throw new StopSSEException();
            }
            return json_encode(compact('chunk'));
        }, 'answer');
        (new SSESwoole($event, $request, $response))->start(0.1);
    }
    private function convertRequest(Request $swooleRequest): \Hyperf\HttpMessage\Server\Request
    {
        $headers = [];
        foreach ($swooleRequest->header as $name => $value) {
            $headers[$name] = $value;
        }

        $method = $swooleRequest->server['request_method'] ?? 'GET';
        $uri = $swooleRequest->server['request_uri'] ?? '/';
        $version = isset($swooleRequest->server['server_protocol']) ? substr($swooleRequest->server['server_protocol'], 5) : '1.1';
        $body = new \Hyperf\HttpMessage\Stream\SwooleStream($swooleRequest->rawContent() ?? '');

        $psr7Request = new \Hyperf\HttpMessage\Server\Request($method, $uri, $headers, $body, $version, $swooleRequest->server);
        $psr7Request = $psr7Request->withQueryParams($swooleRequest->get ?? [])
            ->withParsedBody($swooleRequest->post ?? [])
            ->withCookieParams($swooleRequest->cookie ?? [])
            ->withUploadedFiles($swooleRequest->files ?? []);

        return $psr7Request;
    }





}