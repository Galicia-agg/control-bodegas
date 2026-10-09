<?php

namespace App\Models;

use App\Enums\RateService;
use App\Enums\StorageUnitType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property RateService $service
 * @property StorageUnitType|null $unit_type
 * @property int|null $customer_id
 * @property string $amount
 * @property int $free_days
 */
#[Fillable(['service', 'unit_type', 'customer_id', 'amount', 'free_days', 'is_active'])]
class Rate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service' => RateService::class,
            'unit_type' => StorageUnitType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
