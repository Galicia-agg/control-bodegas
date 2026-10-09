<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $folio
 * @property MovementType $type
 * @property int $warehouse_id
 * @property int|null $destination_warehouse_id
 */
#[Fillable(['folio', 'type', 'warehouse_id', 'destination_warehouse_id', 'supplier_id', 'user_id', 'reference', 'notes', 'occurred_at'])]
class Movement extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<MovementItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MovementItem::class);
    }

    /**
     * Limit the query to movements that touch a warehouse the user can access.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->canAccessAllWarehouses()) {
            return;
        }

        $ids = $user->warehouses()->pluck('warehouses.id');

        $query->where(fn (Builder $q) => $q
            ->whereIn('warehouse_id', $ids)
            ->orWhereIn('destination_warehouse_id', $ids));
    }
}
