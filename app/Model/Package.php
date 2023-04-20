<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use OpenApi\Annotations as OA;

/**
 * @property int $id 
 * @property string $name 
 * @property int $quota 
 * @property int $duration 
 * @property int $status 
 * @property string $price 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property int $level 
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
    protected array $casts = ['id' => 'integer', 'quota' => 'integer', 'duration' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'level' => 'integer'];
}
