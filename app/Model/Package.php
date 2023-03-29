<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     title="Package",
 *     description="Package model",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="quota", type="integer"),
 *     @OA\Property(property="duration", type="integer"),
 *     @OA\Property(property="status", type="integer"),
 *     @OA\Property(property="price", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 */
class Package extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'packages';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'name',
        'quota',
        'duration',
        'price',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'quota' => 'integer', 'duration' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
