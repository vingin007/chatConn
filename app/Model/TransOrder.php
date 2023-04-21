<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use OpenApi\Annotations as OA;
/**
 * @OA\Schema(
 *   schema="TransOrder",
 *   type="object",
 *   description="TransOrder schema",
 *   @OA\Property(
 *     property="order_no",
 *     type="string",
 *     description="Order number"
 *   ),
 *   @OA\Property(
 *     property="user_id",
 *     type="integer",
 *     description="User ID"
 *   ),
 *   @OA\Property(
 *     property="payment_method_id",
 *     type="integer",
 *     description="Payment method ID"
 *   ),
 *   @OA\Property(
 *     property="original_video_id",
 *     type="string",
 *     description="Original video ID"
 *   ),
 *   @OA\Property(
 *     property="original_video_store_name",
 *     type="string",
 *     description="Original video store name"
 *   ),
 *   @OA\Property(
 *     property="transcribed_video_store_name",
 *     type="string",
 *     description="Transcribed video store name"
 *   ),
 *   @OA\Property(
 *     property="translated_subtitle_store_name",
 *     type="string",
 *     description="Translated subtitle store name"
 *   ),
 *   @OA\Property(
 *     property="paid_time",
 *     type="string",
 *     format="date-time",
 *     description="Paid time"
 *   ),
 *   @OA\Property(
 *     property="status",
 *     type="integer",
 *     description="Order status"
 *   ),
 *   @OA\Property(
 *     property="video_duration",
 *     type="string",
 *     description="Video duration"
 *   ),
 *   @OA\Property(
 *     property="order_amount",
 *     type="string",
 *     description="Order amount"
 *   ),
 *   @OA\Property(
 *     property="video_size",
 *     type="integer",
 *     description="Video size"
 *   ),
 *   @OA\Property(
 *     property="created_at",
 *     type="string",
 *     format="date-time",
 *     description="Creation timestamp"
 *   ),
 *   @OA\Property(
 *     property="updated_at",
 *     type="string",
 *     format="date-time",
 *     description="Update timestamp"
 *   ),
 *   @OA\Property(
 *     property="amount",
 *     type="string",
 *     description="Order amount"
 *   ),
 *   @OA\Property(
 *     property="real_duration",
 *     type="integer",
 *     description="Real video duration"
 *   ),
 *   @OA\Property(
 *     property="status_text",
 *     type="string",
 *     readOnly=true,
 *     description="Order status text"
 *   )
 * )
 */
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
 * @property int $real_duration 
 * @property-read mixed $status_text 
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
    protected array $casts = ['user_id' => 'integer', 'payment_method_id' => 'integer', 'status' => 'integer', 'video_size' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'real_duration' => 'integer'];

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
