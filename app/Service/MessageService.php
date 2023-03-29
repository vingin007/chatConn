<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\BusinessException;
use App\Model\Chat;
use App\Model\Message;
use App\Model\User;
use Carbon\Carbon;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Paginator\Paginator;

class MessageService
{
    /**
     * 创建消息.
     *
     * @param int $chatId
     * @param string $content
     * @param bool $isUser
     * @return Message
     */
    public function create($chatRecord): Message
    {
        try {
            $message = new Message();
            $message->chat_id = $chatRecord['chat_id'];
            $message->user_id = $chatRecord['user_id'];
            $message->content = $chatRecord['content'];
            $message->is_user = $chatRecord['is_user'];
            $message->type = $chatRecord['type'];
            $message->store_name = $chatRecord['store_name'];
            $message->url = $chatRecord['url'];
            $message->created_at = $chatRecord['created_at'];
            $message->save();
        }catch (ModelNotFoundException $exception){
            throw new BusinessException('401','消息保存失败');
        }

        return $message;
    }

    /**
     * 假删除消息.
     *
     * @param int $id
     * @return bool
     */
    public function delete(Message $message): bool
    {
        $message->is_deleted = true;
        $message->save();
        return true;
    }

    /**
     * 按时间段统计消息数量.
     *
     * @param int $chatId
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    public function countByDateRange(int $chatId, Carbon $start, Carbon $end): array
    {
        $messages = Message::query()
            ->where('chat_id', $chatId)
            ->where('is_user', false)
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_at']);

        $result = [];
        foreach ($messages as $message) {
            $date = $message->created_at->format('Y-m-d');
            if (! isset($result[$date])) {
                $result[$date] = 0;
            }
            $result[$date]++;
        }

        return $result;
    }

    /**
     * 分页获取消息.
     *
     * @param int $chatId
     * @param int $pageSize
     * @param int $currentPage
     * @return LengthAwarePaginatorInterface|Paginator
     */
    public function paginate(int $chatId, int $pageSize = 10, int $currentPage = 1)
    {
        $query = Message::query()
            ->where('chat_id', $chatId)
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc');

        $messages = $query->paginate($pageSize, ['*'], 'page', $currentPage);
        $messages->getCollection()->transform(function (Message $message) {
            return [
                'id' => $message->id,
                'chat_id' => $message->chat_id,
                'content' => $message->content,
                'created_at' => $message->created_at->toDateTimeString(),
            ];
        });

        return $messages;
    }
}
