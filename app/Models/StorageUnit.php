<?php

namespace App\Models;

use App\Enums\StorageUnitStatus;
use App\Enums\StorageUnitType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $receipt_id
 * @property int|null $parent_id
 * @property StorageUnitType $type
 * @property StorageUnitStatus $status
 * @property CarbonInterface|null $delivered_at
 */
#[Fillable(['receipt_id', 'parent_id', 'type', 'code', 'description', 'pieces', 'weight_kg', 'volume_m3', 'location', 'status', 'delivered_at'])]
class StorageUnit extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StorageUnitType::class,
            'status' => StorageUnitStatus::class,
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Receipt, $this>
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    /**
     * @return BelongsTo<StorageUnit, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<StorageUnit, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
