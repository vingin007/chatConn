<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use OpenApi\Annotations as OA;

/**
 * @property int $id 
 * @property int $type 
 * @property int $user_id 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property string $name 
 * @property-read \Hyperf\Database\Model\Collection|Message[] $messages 
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
