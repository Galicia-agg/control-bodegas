<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\VoucherStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $folio
 * @property string $total
 * @property VoucherStatus $status
 * @property PaymentMethod|null $payment_method
 * @property CarbonInterface $issued_at
 */
#[Fillable(['folio', 'receipt_id', 'delivery_id', 'customer_id', 'user_id', 'total', 'status', 'issued_at', 'paid_at', 'payment_method', 'payment_reference', 'collected_by'])]
class Voucher extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'status' => VoucherStatus::class,
            'payment_method' => PaymentMethod::class,
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
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
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    /**
     * @return HasMany<VoucherLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(VoucherLine::class);
    }

    /**
     * Limit the query to vouchers of receipts the user can access.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->whereIn('receipt_id', Receipt::query()->visibleTo($user)->select('receipts.id'));
    }
}
