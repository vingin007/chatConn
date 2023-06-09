<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Chat",
 *     @OA\Property(property="id", type="integer", description="聊天ID"),
 *     @OA\Property(property="name", type="string", description="聊天名称"),
 *     @OA\Property(property="type", type="integer", description="聊天类型")
 * )
 */
class Chat extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'chat';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'type' => 'integer', 'user_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function messages()
    {
        return $this->hasMany(Message::class,'chat_id','id');
    }
}
