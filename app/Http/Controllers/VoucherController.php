<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\VoucherStatus;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $status = $request->enum('status', VoucherStatus::class);

        $vouchers = Voucher::query()
            ->visibleTo($request->user())
            ->with(['customer:id,name', 'receipt:id,folio', 'delivery:id,folio'])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->whereLike('folio', "%{$search}%")
                ->orWhereHas('customer', fn ($q) => $q->whereLike('name', "%{$search}%"))
                ->orWhereHas('receipt', fn ($q) => $q->whereLike('folio', "%{$search}%"))))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('issued_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('vouchers/Index', [
            'vouchers' => $vouchers,
            'statuses' => VoucherStatus::options(),
            'pendingTotal' => (float) Voucher::visibleTo($request->user())->where('status', VoucherStatus::Pending)->sum('total'),
            'filters' => ['search' => $search, 'status' => $status?->value],
        ]);
    }

    public function show(Request $request, Voucher $voucher): Response
    {
        abort_unless(Voucher::visibleTo($request->user())->whereKey($voucher->id)->exists(), 403);

        $voucher->load([
            'customer:id,name,tax_id,address',
            'receipt:id,folio,transport_mode,transport_document,duca',
            'delivery:id,folio,type,recipient_name',
            'user:id,name',
            'collector:id,name',
            'lines',
        ]);

        return Inertia::render('vouchers/Show', [
            'voucher' => $voucher,
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function pay(Request $request, Voucher $voucher, VoucherService $vouchers): RedirectResponse
    {
        abort_unless(Voucher::visibleTo($request->user())->whereKey($voucher->id)->exists(), 403);

        $data = $request->validate([
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $vouchers->pay($voucher, PaymentMethod::from($data['payment_method']), $data['payment_reference'] ?? null, $request->user());
        $this->toast("Pago del comprobante {$voucher->folio} registrado.");

        return back();
    }
}
