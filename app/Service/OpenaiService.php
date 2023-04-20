<?php
namespace App\Service;

use App\Model\Message;
use App\Model\User;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use OpenAI;
use Psr\EventDispatcher\EventDispatcherInterface;

class OpenaiService
{

    protected $client;
    protected $api_key;

    protected $redis;

    #[Inject]
    private EventDispatcherInterface $eventDispatcher;

    public function __construct()
    {
        $apiKeyService = new ApiKeyService();
        $this->api_key = $apiKeyService->getApiKey();
        $this->client = OpenAI::client($this->api_key);
        $this->redis = di()->get(Redis::class);
    }
    public function validateApiKey(User $user)
    {
        if ($user && $user->api_key_unlocked && $user->api_key_status) {
            return true;
        }

        return false;
    }
    protected function send($message,$user_id,$chatId,$max_tokens = 2000)
    {
        $messages = [];
        $collections = DB::table('message')
            ->where('chat_id',2)
            ->where('user_id',10)
            ->select('id', 'content', 'num', 'is_user', 'created_at')
            ->selectSub(function ($query) {
                $query->from('message AS m2')
                    ->where('chat_id',2)
                    ->where('user_id',10)
                    ->selectRaw('SUM(num)')
                    ->whereRaw('m2.created_at >= message.created_at')
                    ->limit(1);
            }, 'cumulative_tokens')
            ->orderBy('created_at', 'desc')
            ->having('cumulative_tokens', '<=', 2000)
            ->limit(2000)
            ->get();
        if (!empty($collections)){
            foreach ($collections as $collection){
                $role = $collection->is_user == 0 ? 'assistant' : 'user';
                $messages[] = ['role' => $role,'content' => $collection->content];
            }
        }
        $messages[] = $message;
        $client = new GuzzleHttpClient();
        try {
            $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                RequestOptions::HEADERS => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Accept' => 'application/json'
                ],
                RequestOptions::BODY => json_encode([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => $max_tokens,
                    'n' => 1,
                    'stream' => true,
                    'user' => md5($chatId)
                ]),
                'stream' => true,
                'verify' => false,
                'debug' => false,
                'stream_context' => [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ],
            ]);
        } catch (GuzzleException $e) {
            throw $e;
        }
        $content = '';
        $stream = $response->getBody();
        while (!$stream->eof()) {
            $chunk = $stream->read(1024);
            if ($chunk) {
                $content .= $chunk;
            }
        }
        $stream->close();
        return explode("data: ",$content);
    }

    public function text($content,User $user,$chat,$max_tokens = 2000)
    {
        if ($this->validateApiKey($user)){
            $this->api_key = $user->api_key;
        }
        try {
            $info = $this->send($content,$user->id, $chat->id, $max_tokens);
        } catch (GuzzleException $e) {
            throw $e;
        }
        $content = '';
        foreach ($info as $match) {
            $result = json_decode($match, true);
            if (!empty($result['choices'][0]['delta']['content'])){
                $content .= $result['choices'][0]['delta']['content'];
            }
        }
        return $content;
    }

    public function audio($message,$user,$chat,$max_tokens = 2000)
    {
        try {
            $info = $this->send($message,$user->id, $chat->id, $max_tokens);
        } catch (GuzzleException $e) {
            throw $e;
        }
        $content = '';
        foreach ($info as $match) {
            $result = json_decode($match, true);
            if (!empty($result['choices'][0]['delta']['content'])){
                $content .= $result['choices'][0]['delta']['content'];
            }
        }
        return $content;
    }

    public function convertAudioToText($filepath,$fetch_lang = false)
    {
        $response = $this->client->audio()->transcribe([
            'model' => 'whisper-1',
            'file' => fopen($filepath, 'r'),
            'response_format' => 'verbose_json',
        ]);
        return $fetch_lang ? $response :$response->text; // 'Hello, how are you?'

    }

    public function generate_image($prompt,$size,$format = 'url')
    {
        $response = $this->client->images()->create([
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size.'x'.$size,
            'response_format' => $format,
        ]);
        $response = $response->toArray();
        return ['url' =>$response['data'][0]['url']];
    }

    public function edit_image($original,$mask,$prompt,$size,$format = 'url')
    {
        $response = $this->client->images()->edit([
            'image' => fopen($original->getRealPath(), 'r'),
            'mask' => fopen($mask->getRealPath(), 'r'),
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size.'x'.$size,
            'response_format' => $format,
        ]);
        $response = $response->toArray();
        return ['url' =>$response['data'][0]['url']];
    }

    public function variation_image($original,$size,$format = 'url')
    {
        $response = $this->client->images()->variation([
            'image' => fopen($original->getRealPath(), 'r'),
            'n' => 1,
            'size' => $size.'x'.$size,
            'response_format' => $format,
        ]);
        $response = $response->toArray();
        return ['url' =>$response['data'][0]['url']];
    }

    public function chat($prompt)
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $prompt,
            'max_tokens' => 2000,
        ]);
        $response = $response->toArray();
        return $response['choices'][0]['message']['content'];
    }
}
