<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Movement;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @phpstan-type MovementItemData array{product_id: int, quantity: float|int|string, unit_cost?: float|int|string|null}
 * @phpstan-type MovementData array{type: string, warehouse_id: int|string, destination_warehouse_id?: int|string|null, supplier_id?: int|string|null, reference?: string|null, notes?: string|null, occurred_at?: string|null, items: array<int, MovementItemData>}
 */
class InventoryService
{
    /**
     * Register a movement and apply its effect on stock atomically.
     *
     * For adjustments, each item quantity is the counted (final) quantity
     * and the stored item quantity is the resulting difference.
     *
     * @param  MovementData  $data
     */
    public function register(array $data, User $user): Movement
    {
        $type = MovementType::from($data['type']);

        return DB::transaction(function () use ($data, $type, $user) {
            $movement = Movement::create([
                'folio' => $this->nextFolio($type),
                'type' => $type,
                'warehouse_id' => $data['warehouse_id'],
                'destination_warehouse_id' => $type === MovementType::Transfer ? ($data['destination_warehouse_id'] ?? null) : null,
                'supplier_id' => $type === MovementType::Entry ? ($data['supplier_id'] ?? null) : null,
                'user_id' => $user->id,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? now(),
            ]);

            $products = Product::whereIn('id', array_column($data['items'], 'product_id'))->get()->keyBy('id');

            foreach ($data['items'] as $index => $item) {
                $product = $products[$item['product_id']];
                $quantity = round((float) $item['quantity'], 2);
                $origin = $this->lockStock($movement->warehouse_id, $product->id);

                $delta = match ($type) {
                    MovementType::Entry => $quantity,
                    MovementType::Exit, MovementType::Transfer => -$quantity,
                    MovementType::Adjustment => round($quantity - (float) $origin->quantity, 2),
                };

                if ((float) $origin->quantity + $delta < 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Stock insuficiente de {$product->name}: disponible ".number_format((float) $origin->quantity, 2).'.',
                    ]);
                }

                $origin->quantity = round((float) $origin->quantity + $delta, 2);
                $origin->save();

                if ($type === MovementType::Transfer) {
                    $destination = $this->lockStock($movement->destination_warehouse_id, $product->id);
                    $destination->quantity = round((float) $destination->quantity + $quantity, 2);
                    $destination->save();
                }

                if ($type === MovementType::Entry && isset($item['unit_cost']) && (float) $item['unit_cost'] > 0) {
                    $product->update(['cost' => $item['unit_cost']]);
                }

                $movement->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $type === MovementType::Adjustment ? $delta : $quantity,
                    'unit_cost' => $item['unit_cost'] ?? $product->cost,
                ]);
            }

            return $movement;
        });
    }

    private function lockStock(int $warehouseId, int $productId): Stock
    {
        Stock::firstOrCreate(['warehouse_id' => $warehouseId, 'product_id' => $productId], ['quantity' => 0]);

        return Stock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function nextFolio(MovementType $type): string
    {
        $last = Movement::where('type', $type)->lockForUpdate()->latest('id')->value('folio');
        $number = $last ? ((int) substr($last, -6)) + 1 : 1;

        return sprintf('%s-%06d', $type->prefix(), $number);
    }
}
