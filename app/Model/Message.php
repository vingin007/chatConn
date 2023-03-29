<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Message",
 *     type="object",
 *     description="A Message object",
 *     @OA\Property(property="id", type="integer", description="The ID of the message"),
 *     @OA\Property(property="user_id", type="integer", description="The ID of the user"),
 *     @OA\Property(property="chat_id", type="integer", description="The ID of the chat"),
 *     @OA\Property(property="is_user", type="boolean", description="Whether the message is from a user"),
 *     @OA\Property(property="type", type="string", description="The type of the message", enum={"text", "image", "audio"}),
 *     @OA\Property(property="store_name", type="string", description="The name of the file stored on the server"),
 *     @OA\Property(property="url", type="string", description="The url of the file"),
 *     @OA\Property(property="content", type="string", description="The content of the message"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="The time the message was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="The time the message was last updated")
 * )
 */

class Message extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'message';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'chat_id' => 'integer', 'user_id' => 'integer', 'is_user' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function chat()
    {
        return $this->hasOne(Chat::class,'id','chat_id');
    }
}
