<?php

namespace App\Models;

use App\Enums\ReceiptStatus;
use App\Enums\TransportMode;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $folio
 * @property TransportMode $transport_mode
 * @property ReceiptStatus $status
 * @property int $warehouse_id
 * @property int $customer_id
 * @property CarbonInterface $received_at
 */
#[Fillable(['folio', 'customer_id', 'warehouse_id', 'user_id', 'transport_mode', 'transport_document', 'duca', 'carrier', 'origin', 'status', 'received_at', 'notes'])]
class Receipt extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transport_mode' => TransportMode::class,
            'status' => ReceiptStatus::class,
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<StorageUnit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(StorageUnit::class);
    }

    /**
     * Limit the query to receipts stored in a warehouse the user can access.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->canAccessAllWarehouses()) {
            return;
        }

        $query->whereIn('warehouse_id', $user->warehouses()->select('warehouses.id'));
    }
}
