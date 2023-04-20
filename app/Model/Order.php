<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use OpenApi\Annotations as OA;
/**
 * @property string $order_no 
 * @property int $user_id 
 * @property int $paid 
 * @property string $paid_time 
 * @property int $package_id 
 * @property string $payment_method 
 * @property string $package_name 
 * @property int $package_quota 
 * @property int $package_duration 
 * @property string $amount 
 * @property string $expired_at 
 * @property int $status 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property-read User $user 
 * @property-read Package $package 
 */
class Order extends Model
{
    const STATUS_UNPAID = 0;
    const STATUS_PAID = 1;
    const STATUS_CANCELLED = 2;

    const PAYMENT_METHOD_FREE = 'free';
    const PAYMENT_METHOD_WECHAT = 'wechat';
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'orders';

    protected string $primaryKey = 'order_no';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['user_id' => 'integer', 'paid' => 'integer', 'package_id' => 'integer', 'package_quota' => 'integer', 'package_duration' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function user(): \Hyperf\Database\Model\Relations\BelongsTo
    {
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function package(): \Hyperf\Database\Model\Relations\BelongsTo
    {
        return $this->belongsTo(Package::class,'id','package_id');
    }
    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case self::STATUS_UNPAID:
                return '未支付';
            case self::STATUS_PAID:
                return '已支付';
            case self::STATUS_CANCELLED:
                return '已取消';
            default:
                return '未知';
        }
    }
}
