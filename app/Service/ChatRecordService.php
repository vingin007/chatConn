<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Chat;
use App\Model\Message;
use App\Model\User;

class ChatRecordService
{
    /**
     * 存储聊天记录
     * @param User $user
     * @param Chat $chat
     * @param string $content
     * @param string $type
     * @param string $store_name
     * @param string $url
     * @param bool $is_user
     * @return Message|bool
     */
    public function addChatLog(User $user,Chat $chat ,string $content,string $type,string $store_name,string $url,bool $is_user): bool|Message
    {
        $message = New Message();
        $message->user_id = $user->id;
        $message->chat_id = $chat->id;
        $message->content = $content;
        $message->is_user = $is_user;
        $message->type = $type;
        $message->store_name = $store_name;
        $message->url = $url;
        $message->save();
        return $message;
    }
}
