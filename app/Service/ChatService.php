<?php

namespace App\Service;

use App\Model\Chat;
use App\Model\Message;
use App\Model\User;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;

class ChatService
{
    public function createChat(User $user)
    {
        // 创建 Chat
        $chat = new Chat();
        $chat->user_id = $user->id;
        $chat->name = '默认分组';
        $chat->save();
        $this->clear($user);
        return $chat;
    }

    public function deleteChat(User $user,Chat $chat)
    {
        $chat->delete();
        $this->clear($user);
        return true;
    }

    public function getChat(User $user)
    {
        $container = ApplicationContext::getContainer();
        $redis = $container->get(Redis::class);
        if (!$redis->hExists('users_chats',(string)$user->id)){
            $chats = Chat::query()->select(['id','name','type'])->get()->toArray();
            $redis->hSet('users_chats',(string)$user->id,json_encode($chats));
        }
        return json_decode($redis->hGet('users_chats',(string)$user->id),true);
    }

    public function renameChat(User $user,$chatId, $newName)
    {
        // 修改 Chat 的名称
        $chat = Chat::find($chatId);
        if (!$chat) {
            return false;
        }

        $chat->name = $newName;
        $chat->save();
        $this->clear($user);
        return true;
    }


    private function clear(User $user)
    {
        $container = ApplicationContext::getContainer();
        $redis = $container->get(Redis::class);
        if ($redis->hExists('users_chats',(string)$user->id)){
            $redis->hDel('users_chats',(string)$user->id);
        }
        $chats = Chat::query()->where('user_id',$user->id)->select(['id','name','type'])->get()->toArray();
        $redis->hSet('users_chats',(string)$user->id,json_encode($chats));
        return true;
    }
}
