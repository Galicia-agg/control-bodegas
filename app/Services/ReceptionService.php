<?php

namespace App\Services;

use App\Enums\ReceiptStatus;
use App\Enums\StorageUnitType;
use App\Models\Receipt;
use App\Models\StorageUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-type UnitData array{type: string, parent_index?: int|string|null, description?: string|null, pieces?: int|string|null, weight_kg?: float|int|string|null, volume_m3?: float|int|string|null, location?: string|null}
 * @phpstan-type ReceiptData array{customer_id: int|string, warehouse_id: int|string, transport_mode: string, transport_document?: string|null, duca?: string|null, carrier?: string|null, origin?: string|null, received_at?: string|null, notes?: string|null, units: array<int, UnitData>}
 */
class ReceptionService
{
    /**
     * Register a receipt (ingreso) with the storage units that arrived in it.
     *
     * A unit may reference an earlier unit of the same list as its parent
     * (a pallet inside a container, a box on a pallet).
     *
     * @param  ReceiptData  $data
     */
    public function register(array $data, User $user): Receipt
    {
        return DB::transaction(function () use ($data, $user) {
            $receipt = Receipt::create([
                'folio' => $this->nextFolio(),
                'customer_id' => $data['customer_id'],
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => $user->id,
                'transport_mode' => $data['transport_mode'],
                'transport_document' => $data['transport_document'] ?? null,
                'duca' => $data['duca'] ?? null,
                'carrier' => $data['carrier'] ?? null,
                'origin' => $data['origin'] ?? null,
                'status' => ReceiptStatus::Active,
                'received_at' => $data['received_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            /** @var array<int, StorageUnit> $created */
            $created = [];
            $counters = [];

            foreach ($data['units'] as $index => $item) {
                $type = StorageUnitType::from($item['type']);
                $counters[$type->value] = ($counters[$type->value] ?? 0) + 1;

                $parentIndex = isset($item['parent_index']) && $item['parent_index'] !== '' ? (int) $item['parent_index'] : null;

                $created[$index] = $receipt->units()->create([
                    'parent_id' => $parentIndex !== null ? $created[$parentIndex]->id : null,
                    'type' => $type,
                    'code' => sprintf('%s-%s%02d', $receipt->folio, $type->prefix(), $counters[$type->value]),
                    'description' => $item['description'] ?? null,
                    'pieces' => $item['pieces'] ?? null,
                    'weight_kg' => $item['weight_kg'] ?? null,
                    'volume_m3' => $item['volume_m3'] ?? null,
                    'location' => $item['location'] ?? null,
                ]);
            }

            return $receipt;
        });
    }

    private function nextFolio(): string
    {
        $last = Receipt::lockForUpdate()->latest('id')->value('folio');
        $number = $last ? ((int) substr($last, -6)) + 1 : 1;

        return sprintf('ING-%06d', $number);
    }
}
