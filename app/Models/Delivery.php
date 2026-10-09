<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\DeliveryType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $folio
 * @property int $receipt_id
 * @property DeliveryType $type
 * @property DeliveryStatus $status
 * @property CarbonInterface $released_at
 */
#[Fillable(['folio', 'receipt_id', 'user_id', 'carrier_id', 'type', 'status', 'recipient_name', 'recipient_document', 'destination_address', 'destination_phone', 'released_at', 'scheduled_at', 'completed_at', 'proof_note', 'notes'])]
class Delivery extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DeliveryType::class,
            'status' => DeliveryStatus::class,
            'released_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Carrier, $this>
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * @return BelongsToMany<StorageUnit, $this>
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(StorageUnit::class, 'delivery_units');
    }

    /**
     * @return HasOne<Voucher, $this>
     */
    public function voucher(): HasOne
    {
        return $this->hasOne(Voucher::class);
    }

    /**
     * Limit the query to deliveries of receipts the user can access.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->whereIn('receipt_id', Receipt::query()->visibleTo($user)->select('receipts.id'));
    }
}
