<?php

namespace App\Server;

use App\Event\TextMessageSend;
use App\Model\Chat;
use App\Model\Message;
use App\Model\User;
use App\Service\OpenaiService;
use Hhxsv5\SSE\Event;
use Hhxsv5\SSE\SSESwoole;
use Hhxsv5\SSE\StopSSEException;
use Hyperf\Context\Context;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;
use HyperfExtension\Auth\AuthManager;
use HyperfExtension\Jwt\JwtFactory;
use phpseclib3\File\ASN1\Maps\Certificate;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

class EventStreamServer
{
    private AuthManager $authManager;
    public function __construct()
    {
        $container = ApplicationContext::getContainer();
        $this->authManager = new AuthManager($container);
    }

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
        $text = $openaiService->text(['role' => 'user', 'content' => $message->content],$user, $chat,3000);
        // 将文本拆分为 5 个字符的数组
        $chunks = str_split($text, 5);

        // 发送每个字符组
        foreach ($chunks as $chunk) {
            $event = new Event(function () use ($chunk) {
                if (empty($chunk)) {
                    return false; // Return false if no new messages
                }
                $shouldStop = false; // Stop if something happens or to clear connection, browser will retry
                if ($shouldStop) {
                    throw new StopSSEException();
                }
                return json_encode(compact('chunk'));
            }, 'answer');
            (new SSESwoole($event, $request, $response))->start();
        }
        // 发送 SSE 事件，通知客户端关闭连接
        $event = new Event(function () {
            return '';
        }, 'done');
        (new SSESwoole($event, $request, $response))->start();
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