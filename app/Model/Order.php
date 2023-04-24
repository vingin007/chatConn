<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use OpenApi\Annotations as OA;
/**
 * @OA\Schema(
 *     title="Order",
 *     description="Order model",
 *     @OA\Property(property="order_no", type="string", description="Order number"),
 *     @OA\Property(property="user_id", type="integer", description="User ID"),
 *     @OA\Property(property="payment_method", type="string", description="Payment method"),
 *     @OA\Property(property="payment_qrcode", type="string", description="Payment QR code"),
 *     @OA\Property(property="paid", type="integer", description="Whether the order is paid"),
 *     @OA\Property(property="paid_time", type="string", format="datetime", description="Time of payment"),
 *     @OA\Property(property="package_id", type="integer", description="Package ID"),
 *     @OA\Property(property="package_name", type="string", description="Package name"),
 *     @OA\Property(property="package_quota", type="integer", description="Package quota"),
 *     @OA\Property(property="package_duration", type="integer", description="Package duration"),
 *     @OA\Property(property="amount", type="string", description="Amount paid for the package"),
 *     @OA\Property(property="expired_at", type="string", format="datetime", description="Expiration time"),
 *     @OA\Property(property="status", type="integer", description="Order status"),
 *     @OA\Property(property="created_at", type="string", format="datetime", description="Creation time"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", description="Update time"),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="package", ref="#/components/schemas/Package"),
 * )
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

    protected string $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['order_no' => 'string','user_id' => 'integer', 'paid' => 'integer', 'package_id' => 'integer', 'package_quota' => 'integer', 'package_duration' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

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
