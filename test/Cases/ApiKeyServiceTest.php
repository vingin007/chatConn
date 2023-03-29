<?php
namespace HyperfTest\Cases;

use App\Service\ApiKeyService;
use App\Model\User;
use App\Exception\BusinessException;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Redis\Redis;
use Hyperf\Utils\Str;
use PHPUnit\Framework\TestCase;

class ApiKeyServiceTest extends TestCase
{
    private $apiKeyService;
    private $redis;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $container = ApplicationContext::getContainer();
        $this->apiKeyService = $container->get(ApiKeyService::class);
        $this->redis = $container->get(Redis::class);

        // 创建一个用户
        $this->user = new User();
        $this->user->email = 'test@example.com';
        $this->user->username = 'Test User';
        $this->user->password = password_hash('password', PASSWORD_DEFAULT);
        $this->user->referral_count = 10;
        $this->user->save();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // 删除用户
        if ($this->user) {
            $this->user->delete();
        }
    }

    public function testBindApiKey()
    {
        // 绑定API Key
        $apiKey = Str::random(32);
        $this->assertTrue($this->apiKeyService->bind($apiKey, $this->user));

        // 检查用户的API Key是否设置正确
        $this->user->refresh();
        $this->assertEquals($apiKey, $this->user->api_key);

        // 检查API Key是否添加到Redis中
        $this->assertTrue($this->redis->zscore('active_api_keys', $apiKey) !== false);
    }

    public function testRemoveApiKey()
    {
        // 绑定API Key
        $apiKey = Str::random(32);
        $this->apiKeyService->bind($apiKey, $this->user);

        // 删除API Key
        $this->assertTrue($this->apiKeyService->remove($this->user));

        // 检查用户的API Key是否为空
        $this->user->refresh();
        $this->assertEmpty($this->user->api_key);

        // 检查API Key是否从Redis中删除
        $this->assertFalse($this->redis->zscore('active_api_keys', $apiKey));
    }
    public function testGetApiKey()
    {
        // 设置平台 API Key
        putenv('OPENAI_KEY=platform_key');

        // 准备 Redis
        $container = ApplicationContext::getContainer();
        $redis = $container->get(Redis::class);

        // 测试平台 API Key
        $redis->zremrangebyrank('active_api_keys', 0, -1);
        $apiKeyService = new ApiKeyService();
        $apiKey = $apiKeyService->getApiKey();
        $this->assertEquals('platform_key', $apiKey);

        // 测试没有激活的用户 API Key
        $redis->zremrangebyrank('active_api_keys', 0, -1);
        $apiKey = $apiKeyService->getApiKey();
        $this->assertEquals('platform_key', $apiKey);

        // 测试用户 API Key
        $userApiKey = 'user_key';
        $redis->zremrangebyrank('active_api_keys', 0, -1);
        $redis->zadd('active_api_keys', time(), $userApiKey);
        $redis->set("api_key_usage:{$userApiKey}", 1);
        $apiKey = $apiKeyService->getApiKey();
        $this->assertEquals($apiKey, $apiKey);

        // 测试多个用户 API Key
        $userApiKeys = ['key1', 'key2', 'key3'];
        $redis->zremrangebyrank('active_api_keys', 0, -1);
        foreach ($userApiKeys as $apiKey) {
            $redis->zadd('active_api_keys', time(), $apiKey);
        }
        $redis->set("api_key_usage:key1", 1);
        $redis->set("api_key_usage:key2", 2);
        $redis->set("api_key_usage:key3", 3);
        $apiKey = $apiKeyService->getApiKey();
        $this->assertEquals($apiKey, $apiKey);

        // 测试使用次数最少的API Key有多个时的随机选择
        $redis->zremrangebyrank('active_api_keys', 0, -1);
        $userApiKeys = ['key1', 'key2', 'key3','platform_key'];
        foreach ($userApiKeys as $apiKey) {
            $redis->zadd('active_api_keys', time(), $apiKey);
        }
        $redis->set("api_key_usage:key1", 1);
        $redis->set("api_key_usage:key2", 2);
        $redis->set("api_key_usage:key3", 2);
        $selectedKeys = ['platform_key'];
        foreach (range(1, 10) as $i) {
            $apiKey = $apiKeyService->getApiKey();
            $this->assertTrue(in_array($apiKey, $userApiKeys));
            $selectedKeys[] = $apiKey;
        }
        $this->assertTrue(in_array('platform_key', $selectedKeys));
    }


    public function testGetApiKeyWithNoUserKeys()
    {
        // 获取API Key
        $selectedApiKey = $this->apiKeyService->getApiKey();

        // 确保选择的API Key是平台API Key
        $this->assertEquals(getenv('OPENAI_KEY'), $selectedApiKey);
    }

}
