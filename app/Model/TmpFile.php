<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $user_id 
 * @property string $url 
 * @property int $type 
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TmpFile extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'tmp_file';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'type' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
