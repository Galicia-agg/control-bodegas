<?php

namespace App\Models;

use App\Enums\CarrierKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CarrierKind $kind
 */
#[Fillable(['name', 'kind', 'driver_name', 'vehicle_plate', 'phone', 'is_active'])]
class Carrier extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => CarrierKind::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Delivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
