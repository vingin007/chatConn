<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use HyperfExtension\Auth\Authenticatable;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use HyperfExtension\Jwt\Contracts\JwtSubjectInterface;
use OpenApi\Annotations as OA;

/**
 * @property int $id 
 * @property string $username 
 * @property string $password 
 * @property string $email 
 * @property string $mobile 
 * @property string $wechat_openid 
 * @property string $telegram_id 
 * @property string $register_time 
 * @property int $quota 
 * @property int $level 
 * @property int $email_valid 
 * @property string $expire_time 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property string $api_key 
 * @property int $api_key_unlocked 
 * @property int $api_key_status 
 * @property int $referral_count 
 * @property int $referrer_id 
 * @property int $is_paid 
 * @property string $bind_time 
 * @property string $paid_time 
 * @property-read \Hyperf\Database\Model\Collection|Chat[] $chats 
 * @property-read User $referrer 
 * @property-read \Hyperf\Database\Model\Collection|User[] $referrals 
 * @property-read \Hyperf\Database\Model\Collection|Order[] $orders 
 */
class User extends Model implements AuthenticatableInterface ,JwtSubjectInterface
{
    use Authenticatable;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'users';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['username','password'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'quota' => 'integer', 'level' => 'integer', 'email_valid' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'api_key_unlocked' => 'integer', 'api_key_status' => 'integer', 'referral_count' => 'integer', 'referrer_id' => 'integer', 'is_paid' => 'integer'];

    public function chats()
    {
        return $this->hasMany(Chat::class,'user_id','id');
    }

    public function getJwtIdentifier()
    {
        return $this->getKey();
    }

    public function getJwtCustomClaims(): array
    {
        return [
            'guard' => 'mini'    // 添加一个自定义载荷保存守护名称，方便后续判断
        ];
    }
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

}
