<?php

namespace App\Services;

use App\Enums\RateService;
use App\Enums\StorageUnitStatus;
use App\Models\Rate;
use App\Models\Receipt;
use App\Models\StorageUnit;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * @phpstan-type Line array{unit_id: int, code: string, type: string, days: int, free_days: int, billable_days: int, rate: float|null, amount: float}
 * @phpstan-type Accrual array{lines: list<Line>, total: float, missing_rates: int}
 */
class BillingService
{
    /**
     * Storage charges accrued by the billable units of a receipt.
     *
     * Only units without contents are billed (a container holding pallets is
     * billed through its pallets). Days are calendar days counted from the
     * receipt date, the first day included, up to the unit's delivery date or
     * `$asOf` while it is still stored. Free days are not charged.
     *
     * @return Accrual
     */
    public function storageAccrual(Receipt $receipt, ?CarbonInterface $asOf = null): array
    {
        $asOf ??= now();
        $rates = $this->activeRates($receipt->customer_id, RateService::Storage);

        $units = $receipt->relationLoaded('units') ? $receipt->units : $receipt->units()->get();
        $parents = $units->pluck('parent_id')->filter()->flip();

        $lines = [];
        $missing = 0;

        foreach ($units as $unit) {
            if ($parents->has($unit->id)) {
                continue;
            }

            $rate = $this->resolve($rates, $unit->type->value);
            $until = $unit->status === StorageUnitStatus::Delivered && $unit->delivered_at ? $unit->delivered_at : $asOf;
            $line = $this->line($unit, $receipt->received_at, $until, $rate);

            if ($rate === null) {
                $missing++;
            }

            $lines[] = $line;
        }

        return [
            'lines' => $lines,
            'total' => round(array_sum(array_column($lines, 'amount')), 2),
            'missing_rates' => $missing,
        ];
    }

    /**
     * @return Collection<int, Rate>
     */
    private function activeRates(int $customerId, RateService $service): Collection
    {
        return Rate::query()
            ->where('service', $service)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('customer_id')->orWhere('customer_id', $customerId))
            ->get();
    }

    /**
     * The most specific rate wins: customer + type, customer, type, general.
     *
     * @param  Collection<int, Rate>  $rates
     */
    private function resolve(Collection $rates, string $unitType): ?Rate
    {
        return $rates
            ->filter(fn (Rate $r) => $r->unit_type === null || $r->unit_type->value === $unitType)
            ->sortByDesc(fn (Rate $r) => ($r->customer_id !== null ? 2 : 0) + ($r->unit_type !== null ? 1 : 0))
            ->first();
    }

    /**
     * @return Line
     */
    private function line(StorageUnit $unit, CarbonInterface $from, CarbonInterface $until, ?Rate $rate): array
    {
        $days = max(1, (int) $from->copy()->startOfDay()->diffInDays($until->copy()->startOfDay()) + 1);
        $free = $rate !== null ? $rate->free_days : 0;
        $billable = max(0, $days - $free);

        return [
            'unit_id' => $unit->id,
            'code' => $unit->code,
            'type' => $unit->type->value,
            'days' => $days,
            'free_days' => $free,
            'billable_days' => $billable,
            'rate' => $rate ? (float) $rate->amount : null,
            'amount' => $rate ? round($billable * (float) $rate->amount, 2) : 0.0,
        ];
    }
}
