<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\DeliveryType;
use App\Enums\PaymentMethod;
use App\Enums\ReceiptStatus;
use App\Enums\StorageUnitStatus;
use App\Enums\VoucherStatus;
use App\Models\Delivery;
use App\Models\Receipt;
use App\Models\StorageUnit;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @phpstan-type DeliveryData array{unit_ids: array<int, int|string>, type: string, recipient_name: string, recipient_document?: string|null, notes?: string|null, carrier_id?: int|string|null, scheduled_at?: string|null, destination_address?: string|null, destination_phone?: string|null, delivery_fee?: float|int|string|null, paid?: bool, payment_method?: string|null, payment_reference?: string|null}
 */
class DeliveryService
{
    public function __construct(private BillingService $billing) {}

    /**
     * Release units of a receipt to the customer (partial or total) and issue
     * the voucher that charges storage, handling and, for home deliveries,
     * the delivery fee.
     *
     * Storage stops accruing for the released units at this moment, so every
     * unit is charged exactly once, on the voucher of the delivery that takes
     * it out. Selecting a unit that holds others releases its contents too.
     *
     * @param  DeliveryData  $data
     */
    public function register(Receipt $receipt, array $data, User $user): Delivery
    {
        return DB::transaction(function () use ($receipt, $data, $user) {
            Receipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            $units = $receipt->units()->lockForUpdate()->get()->keyBy('id');
            $children = $units->groupBy('parent_id');
            $now = now();
            $home = $data['type'] === DeliveryType::Home->value;

            $release = $this->expandSelection($units, $children, array_values(array_map('intval', $data['unit_ids'])));

            if ($release->isEmpty()) {
                throw ValidationException::withMessages(['unit_ids' => 'Selecciona al menos una unidad que siga almacenada.']);
            }

            $billable = $release->filter(fn (StorageUnit $u) => ! $children->has($u->id))->values();
            $lines = $this->billing->releaseLines(
                $receipt,
                $billable,
                $now,
                $home,
                isset($data['delivery_fee']) && $data['delivery_fee'] !== '' ? (float) $data['delivery_fee'] : null,
            );

            $delivery = Delivery::create([
                'folio' => $this->nextFolio(Delivery::class, 'ENT'),
                'receipt_id' => $receipt->id,
                'user_id' => $user->id,
                'carrier_id' => $home ? ($data['carrier_id'] ?? null) : null,
                'type' => $data['type'],
                'status' => $home ? DeliveryStatus::Scheduled : DeliveryStatus::Delivered,
                'recipient_name' => $data['recipient_name'],
                'recipient_document' => $data['recipient_document'] ?? null,
                'destination_address' => $home ? ($data['destination_address'] ?? null) : null,
                'destination_phone' => $home ? ($data['destination_phone'] ?? null) : null,
                'released_at' => $now,
                'scheduled_at' => $home ? ($data['scheduled_at'] ?? null) : null,
                'completed_at' => $home ? null : $now,
                'notes' => $data['notes'] ?? null,
            ]);

            $delivery->units()->attach($release->pluck('id')->all());

            foreach ($release as $unit) {
                $unit->update(['status' => StorageUnitStatus::Delivered, 'delivered_at' => $now]);
            }

            $this->closeEmptyParents($units, $children, $now);
            $this->refreshReceiptStatus($receipt, $units);

            $paid = (bool) ($data['paid'] ?? false);
            $voucher = Voucher::create([
                'folio' => $this->nextFolio(Voucher::class, 'COB'),
                'receipt_id' => $receipt->id,
                'delivery_id' => $delivery->id,
                'customer_id' => $receipt->customer_id,
                'user_id' => $user->id,
                'total' => round(array_sum(array_column($lines, 'amount')), 2),
                'status' => $paid ? VoucherStatus::Paid : VoucherStatus::Pending,
                'issued_at' => $now,
                'paid_at' => $paid ? $now : null,
                'payment_method' => $paid ? ($data['payment_method'] ?? PaymentMethod::Cash->value) : null,
                'payment_reference' => $paid ? ($data['payment_reference'] ?? null) : null,
                'collected_by' => $paid ? $user->id : null,
            ]);

            foreach ($lines as $line) {
                $voucher->lines()->create($line);
            }

            return $delivery;
        });
    }

    /**
     * Move a home delivery forward: scheduled → on route → delivered.
     */
    public function advance(Delivery $delivery, DeliveryStatus $to, ?string $recipient = null, ?string $proofNote = null): void
    {
        $allowed = match ($delivery->status) {
            DeliveryStatus::Scheduled => DeliveryStatus::OnRoute,
            DeliveryStatus::OnRoute => DeliveryStatus::Delivered,
            default => null,
        };

        if ($delivery->type !== DeliveryType::Home || $allowed !== $to) {
            throw ValidationException::withMessages(['status' => 'La entrega no puede pasar a ese estado.']);
        }

        $delivery->update([
            'status' => $to,
            'completed_at' => $to === DeliveryStatus::Delivered ? now() : null,
            'recipient_name' => $to === DeliveryStatus::Delivered && $recipient ? $recipient : $delivery->recipient_name,
            'proof_note' => $to === DeliveryStatus::Delivered ? $proofNote : $delivery->proof_note,
        ]);
    }

    /**
     * Cancel a home delivery that has not reached the customer: the units go
     * back to storage and its voucher is voided. A paid voucher blocks it.
     */
    public function cancel(Delivery $delivery): void
    {
        DB::transaction(function () use ($delivery) {
            $delivery = Delivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            $voucher = $delivery->voucher;

            if ($delivery->type !== DeliveryType::Home || ! in_array($delivery->status, [DeliveryStatus::Scheduled, DeliveryStatus::OnRoute], true)) {
                throw ValidationException::withMessages(['status' => 'Solo se pueden cancelar entregas a domicilio programadas o en ruta.']);
            }

            if ($voucher !== null && $voucher->status === VoucherStatus::Paid) {
                throw ValidationException::withMessages(['status' => 'El comprobante ya fue cobrado; no se puede cancelar la entrega.']);
            }

            $receipt = $delivery->receipt;
            $units = $receipt->units()->lockForUpdate()->get()->keyBy('id');
            $children = $units->groupBy('parent_id');

            foreach ($delivery->units as $unit) {
                $units[$unit->id]->update(['status' => StorageUnitStatus::Stored, 'delivered_at' => null]);
            }

            // A container closed automatically reopens when any content returns.
            do {
                $reopened = false;

                foreach ($units as $unit) {
                    if ($unit->status === StorageUnitStatus::Delivered
                        && $children->has($unit->id)
                        && $children[$unit->id]->contains(fn (StorageUnit $c) => $c->status === StorageUnitStatus::Stored)) {
                        $unit->update(['status' => StorageUnitStatus::Stored, 'delivered_at' => null]);
                        $reopened = true;
                    }
                }
            } while ($reopened);

            $voucher?->update(['status' => VoucherStatus::Voided]);
            $delivery->update(['status' => DeliveryStatus::Cancelled]);
            $this->refreshReceiptStatus($receipt, $units);
        });
    }

    /**
     * Stored units selected plus the stored units they contain.
     *
     * @param  Collection<int, StorageUnit>  $units
     * @param  Collection<int|string, EloquentCollection<int, StorageUnit>>  $children
     * @param  list<int>  $ids
     * @return Collection<int, StorageUnit>
     */
    private function expandSelection(Collection $units, Collection $children, array $ids): Collection
    {
        $selected = [];

        $add = function (StorageUnit $unit) use (&$add, &$selected, $children): void {
            if ($unit->status !== StorageUnitStatus::Stored || isset($selected[$unit->id])) {
                return;
            }

            $selected[$unit->id] = $unit;

            foreach ($children->get($unit->id, collect()) as $child) {
                $add($child);
            }
        };

        foreach ($ids as $id) {
            if (! $units->has($id)) {
                throw ValidationException::withMessages(['unit_ids' => 'Una de las unidades no pertenece a este ingreso.']);
            }

            $add($units[$id]);
        }

        return collect(array_values($selected));
    }

    /**
     * A unit whose contents have all left is closed with them.
     *
     * @param  Collection<int, StorageUnit>  $units
     * @param  Collection<int|string, EloquentCollection<int, StorageUnit>>  $children
     */
    private function closeEmptyParents(Collection $units, Collection $children, mixed $at): void
    {
        do {
            $closed = false;

            foreach ($units as $unit) {
                if ($unit->status === StorageUnitStatus::Stored
                    && $children->has($unit->id)
                    && $children[$unit->id]->every(fn (StorageUnit $c) => $c->status === StorageUnitStatus::Delivered)) {
                    $unit->update(['status' => StorageUnitStatus::Delivered, 'delivered_at' => $at]);
                    $closed = true;
                }
            }
        } while ($closed);
    }

    /**
     * @param  Collection<int, StorageUnit>  $units
     */
    private function refreshReceiptStatus(Receipt $receipt, Collection $units): void
    {
        $delivered = $units->filter(fn (StorageUnit $u) => $u->status === StorageUnitStatus::Delivered)->count();

        $receipt->update(['status' => match (true) {
            $delivered === 0 => ReceiptStatus::Active,
            $delivered === $units->count() => ReceiptStatus::Finished,
            default => ReceiptStatus::Partial,
        }]);
    }

    /**
     * @param  class-string<Delivery|Voucher>  $model
     */
    private function nextFolio(string $model, string $prefix): string
    {
        $last = $model::lockForUpdate()->latest('id')->value('folio');
        $number = $last ? ((int) substr($last, -6)) + 1 : 1;

        return sprintf('%s-%06d', $prefix, $number);
    }
}
