<?php
namespace HyperfTest\Cases;

use App\Service\ChatService;
use App\Model\User;
use App\Model\Chat;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use PHPUnit\Framework\TestCase;

class ChatServiceTest extends TestCase
{
    private $chatService;
    private $redis;
    private $user;
    private $chat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chatService = new ChatService();
        $this->redis = ApplicationContext::getContainer()->get(Redis::class);

        // 初始化测试用户和 Chat
        $this->user = User::query()->find(10);

        $this->chat = new Chat();
        $this->chat->user_id = $this->user->id;
        $this->chat->name = '测试分组';
        $this->chat->save();
    }

    protected function tearDown(): void
    {
        // 删除测试用户和 Chat
        $this->chat->delete();

        parent::tearDown();
    }

    public function testCreateChat()
    {
        // 测试创建 Chat
        $chat = $this->chatService->createChat($this->user);
        $this->assertInstanceOf(Chat::class, $chat);
        $this->assertEquals($chat->user_id, $this->user->id);
    }

    public function testDeleteChat()
    {
        // 测试删除 Chat
        $result = $this->chatService->deleteChat($this->user, $this->chat);
        $this->assertTrue($result);
    }

    public function testGetChat()
    {
        // 测试获取 Chat
        $chats = $this->chatService->getChat($this->user);
        $this->assertIsArray($chats);
        $this->assertGreaterThan(0, count($chats));
    }

    public function testRenameChat()
    {
        // 测试修改 Chat 名称
        $newName = '新分组名称';
        $result = $this->chatService->renameChat($this->user, $this->chat->id, $newName);
        $this->assertTrue($result);

        // 检查修改是否成功
        $chat = Chat::find($this->chat->id);
        $this->assertEquals($chat->name, $newName);
    }

}
