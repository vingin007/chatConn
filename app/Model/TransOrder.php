<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property string $order_no 
 * @property int $user_id 
 * @property int $payment_method_id 
 * @property string $original_video_id 
 * @property string $original_video_store_name 
 * @property string $transcribed_video_store_name 
 * @property string $translated_subtitle_store_name 
 * @property string $paid_time 
 * @property int $status 
 * @property string $video_duration 
 * @property string $order_amount 
 * @property int $video_size 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property string $amount 
 */
class TransOrder extends Model
{
    const STATUS_UNPAID = 0;
    const STATUS_PAID = 1;
    const STATUS_FINISH = 2;
    const STATUS_CANCELLED = 3;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'trans_orders';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['user_id' => 'integer', 'payment_method_id' => 'integer', 'status' => 'integer', 'video_size' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case self::STATUS_UNPAID:
                return '未支付';
            case self::STATUS_PAID:
                return '已支付';
            case self::STATUS_FINISH:
                return '已完成';
            case self::STATUS_CANCELLED:
                return '已取消';
            default:
                return '未知';
        }
    }
}
