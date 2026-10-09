<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\VoucherStatus;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    /**
     * Register the payment of a pending voucher.
     */
    public function pay(Voucher $voucher, PaymentMethod $method, ?string $reference, User $user): void
    {
        DB::transaction(function () use ($voucher, $method, $reference, $user) {
            $voucher = Voucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();

            if ($voucher->status !== VoucherStatus::Pending) {
                throw ValidationException::withMessages(['payment_method' => 'El comprobante no está pendiente de pago.']);
            }

            $voucher->update([
                'status' => VoucherStatus::Paid,
                'paid_at' => now(),
                'payment_method' => $method,
                'payment_reference' => $reference,
                'collected_by' => $user->id,
            ]);
        });
    }
}
