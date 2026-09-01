<?php

namespace App\Models;

use App\Enums\SharePermission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $shareable_type
 * @property int $shareable_id
 * @property int $shared_by
 * @property int $shared_with
 * @property SharePermission $permission
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Share extends Model
{
    /** @use HasFactory<\Database\Factories\ShareFactory> */
    use HasFactory;

    protected $fillable = [
        'shareable_type',
        'shareable_id',
        'shared_by',
        'shared_with',
        'permission',
    ];

    protected function casts(): array
    {
        return [
            'permission' => SharePermission::class,
        ];
    }

    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with');
    }
}
