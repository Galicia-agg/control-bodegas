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
 * @phpstan-type Line array{unit_id: int, code: string, type: string, stored: bool, days: int, free_days: int, billable_days: int, rate: float|null, amount: float}
 * @phpstan-type Accrual array{lines: list<Line>, total: float, pending_total: float, missing_rates: int}
 * @phpstan-type VoucherLineData array{concept: RateService, description: string, quantity: float, unit_price: float, amount: float, storage_unit_id: int|null}
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
     * `pending_total` covers the units still stored; delivered units were
     * already charged on their delivery voucher.
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
            $stored = $unit->status === StorageUnitStatus::Stored;
            $until = ! $stored && $unit->delivered_at ? $unit->delivered_at : $asOf;

            if ($rate === null && $stored) {
                $missing++;
            }

            $lines[] = $this->storageLine($unit, $receipt->received_at, $until, $rate);
        }

        return [
            'lines' => $lines,
            'total' => round(array_sum(array_column($lines, 'amount')), 2),
            'pending_total' => round(array_sum(array_column(array_filter($lines, fn (array $l) => $l['stored']), 'amount')), 2),
            'missing_rates' => $missing,
        ];
    }

    /**
     * Voucher lines for releasing units from the warehouse: storage and
     * handling per billable unit, plus the home-delivery fee when applicable.
     *
     * @param  Collection<int, StorageUnit>  $billable  Units without contents that leave.
     * @return list<VoucherLineData>
     */
    public function releaseLines(Receipt $receipt, Collection $billable, CarbonInterface $at, bool $home, ?float $feeOverride = null): array
    {
        $storage = $this->activeRates($receipt->customer_id, RateService::Storage);
        $handling = $this->activeRates($receipt->customer_id, RateService::Handling);
        $lines = [];

        foreach ($billable as $unit) {
            $rate = $this->resolve($storage, $unit->type->value);
            $line = $this->storageLine($unit, $receipt->received_at, $at, $rate);

            $lines[] = [
                'concept' => RateService::Storage,
                'description' => sprintf(
                    'Almacenaje %s: %d día(s)%s%s',
                    $unit->code,
                    $line['days'],
                    $line['free_days'] > 0 ? ", {$line['free_days']} libre(s)" : '',
                    $rate === null ? ' — sin tarifa' : '',
                ),
                'quantity' => (float) $line['billable_days'],
                'unit_price' => $line['rate'] ?? 0.0,
                'amount' => $line['amount'],
                'storage_unit_id' => $unit->id,
            ];

            $handlingRate = $this->resolve($handling, $unit->type->value);

            if ($handlingRate !== null) {
                $lines[] = [
                    'concept' => RateService::Handling,
                    'description' => "Manejo de carga {$unit->code}",
                    'quantity' => 1.0,
                    'unit_price' => (float) $handlingRate->amount,
                    'amount' => round((float) $handlingRate->amount, 2),
                    'storage_unit_id' => $unit->id,
                ];
            }
        }

        if ($home) {
            $fee = $feeOverride ?? $this->deliveryFee($receipt->customer_id);

            if ($fee > 0) {
                $lines[] = [
                    'concept' => RateService::Delivery,
                    'description' => 'Entrega a domicilio',
                    'quantity' => 1.0,
                    'unit_price' => $fee,
                    'amount' => round($fee, 2),
                    'storage_unit_id' => null,
                ];
            }
        }

        return $lines;
    }

    /**
     * Estimated storage + handling per unit if it left now (for previews).
     *
     * @param  Collection<int, StorageUnit>  $billable
     * @return array<int, float>
     */
    public function estimateByUnit(Receipt $receipt, Collection $billable, CarbonInterface $at): array
    {
        $totals = [];

        foreach ($this->releaseLines($receipt, $billable, $at, false) as $line) {
            $id = (int) $line['storage_unit_id'];
            $totals[$id] = round(($totals[$id] ?? 0) + $line['amount'], 2);
        }

        return $totals;
    }

    public function deliveryFee(int $customerId): float
    {
        $rate = $this->resolve($this->activeRates($customerId, RateService::Delivery), null);

        return $rate !== null ? (float) $rate->amount : 0.0;
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
    private function resolve(Collection $rates, ?string $unitType): ?Rate
    {
        return $rates
            ->filter(fn (Rate $r) => $r->unit_type === null || $r->unit_type->value === $unitType)
            ->sortByDesc(fn (Rate $r) => ($r->customer_id !== null ? 2 : 0) + ($r->unit_type !== null ? 1 : 0))
            ->first();
    }

    /**
     * @return Line
     */
    private function storageLine(StorageUnit $unit, CarbonInterface $from, CarbonInterface $until, ?Rate $rate): array
    {
        $days = max(1, (int) $from->copy()->startOfDay()->diffInDays($until->copy()->startOfDay()) + 1);
        $free = $rate !== null ? $rate->free_days : 0;
        $billable = max(0, $days - $free);

        return [
            'unit_id' => $unit->id,
            'code' => $unit->code,
            'type' => $unit->type->value,
            'stored' => $unit->status === StorageUnitStatus::Stored,
            'days' => $days,
            'free_days' => $free,
            'billable_days' => $billable,
            'rate' => $rate ? (float) $rate->amount : null,
            'amount' => $rate ? round($billable * (float) $rate->amount, 2) : 0.0,
        ];
    }
}
