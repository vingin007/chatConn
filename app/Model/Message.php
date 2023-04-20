<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use OpenApi\Annotations as OA;

/**
 * @property int $id 
 * @property int $chat_id 
 * @property int $user_id 
 * @property string $content 
 * @property int $num 
 * @property int $is_user 
 * @property string $type 
 * @property string $store_name 
 * @property string $url 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property int $voice_duration 
 * @property-read Chat $chat 
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
    protected array $casts = ['id' => 'integer', 'chat_id' => 'integer', 'user_id' => 'integer', 'num' => 'integer', 'is_user' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'voice_duration' => 'integer'];

    public function chat()
    {
        return $this->hasOne(Chat::class,'id','chat_id');
    }
}
