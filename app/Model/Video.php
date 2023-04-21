<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use OpenApi\Annotations as OA;
/**
 * @OA\Schema(
 *   title="Video",
 *   type="object",
 *   description="Video schema",
 *   @OA\Property(property="id", type="integer", description="Video ID"),
 *   @OA\Property(property="user_id", type="integer", description="User ID"),
 *   @OA\Property(property="store_name", type="string", description="Store name"),
 *   @OA\Property(property="size", type="integer", description="Video size"),
 *   @OA\Property(property="duration", type="string", description="Video duration"),
 *   @OA\Property(property="format", type="string", description="Video format"),
 *   @OA\Property(property="hash", type="string", description="Video hash"),
 *   @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", description="Update timestamp"),
 *   @OA\Property(property="status", type="integer", description="Video status"),
 * )

/**
 * @property int $id
 * @property int $user_id
 * @property string $store_name
 * @property int $size
 * @property string $duration
 * @property string $format
 * @property string $hash
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $status
 */
class Video extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'videos';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'size' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'status' => 'integer'];
}
