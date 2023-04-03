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
        $number = $this->countTextElements($content);
        $message->num = $number;
        $message->save();
        return $message;
    }
    function countTextElements($text) {
        // 计算单词数量
        $words = str_word_count($text, 0, '0123456789');
        // 计算中文汉字数量
        $chinese = preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $text, $matches);
        // 计算中文标点符号数量
        $chinesePunctuation = preg_match_all('/[\x{3000}-\x{303f}\x{2000}-\x{206f}]/u', $text, $matches);
        // 计算标点符号数量
        $punctuation = preg_match_all('/[\p{P}\p{Zs}\p{Zp}]/u', $text, $matches);
        // 计算数字数量
        $numbers = preg_match_all('/\b\d+\b/', $text, $matches);
        // 计算换行符数量
        $newlines = substr_count($text, "\n");

        return $words + $chinese + $chinesePunctuation + $punctuation + $numbers + $newlines;
    }




}
