<?php

namespace App\Http\Controllers;

use App\Enums\ReceiptStatus;
use App\Enums\VoucherStatus;
use App\Models\Customer;
use App\Models\Receipt;
use App\Models\Voucher;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();

        return Inertia::render('customers/Index', [
            'customers' => Customer::query()
                ->withCount('receipts')
                ->when($search, fn ($q) => $q->where(fn ($q) => $q
                    ->whereLike('name', "%{$search}%")
                    ->orWhereLike('tax_id', "%{$search}%")
                    ->orWhereLike('contact_name', "%{$search}%")))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Account statement: what has been billed, collected and what is still
     * accruing on the customer's stored merchandise.
     */
    public function show(Request $request, Customer $customer, BillingService $billing): Response
    {
        $user = $request->user();
        $canSeeMoney = $user->can('vouchers.view');

        $receipts = Receipt::query()
            ->visibleTo($user)
            ->where('customer_id', $customer->id)
            ->with(['warehouse:id,name', 'units'])
            ->withCount('units')
            ->latest('received_at')
            ->get();

        $vouchers = Voucher::query()->visibleTo($user)->where('customer_id', $customer->id)
            ->where('status', '!=', VoucherStatus::Voided)
            ->get()
            ->groupBy('receipt_id');

        $rows = $receipts->map(function (Receipt $r) use ($vouchers, $billing, $canSeeMoney) {
            $own = $vouchers->get($r->id, collect());

            return [
                'id' => $r->id,
                'folio' => $r->folio,
                'status' => $r->status->value,
                'received_at' => $r->received_at,
                'warehouse' => $r->warehouse->name,
                'units_count' => $r->units_count,
                'billed' => $canSeeMoney ? (float) $own->sum('total') : null,
                'paid' => $canSeeMoney ? (float) $own->where('status', VoucherStatus::Paid)->sum('total') : null,
                'pending' => $canSeeMoney ? (float) $own->where('status', VoucherStatus::Pending)->sum('total') : null,
                'accruing' => $canSeeMoney && $r->status !== ReceiptStatus::Finished ? $billing->storageAccrual($r)['pending_total'] : ($canSeeMoney ? 0.0 : null),
            ];
        });

        return Inertia::render('customers/Show', [
            'customer' => $customer,
            'receipts' => $rows,
            'canSeeMoney' => $canSeeMoney,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Customer::create($this->validated($request));
        $this->toast('Cliente creado.');

        return back();
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request, $customer));
        $this->toast('Cliente actualizado.');

        return back();
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->receipts()->exists()) {
            $this->toast('El cliente tiene ingresos registrados; desactívalo en lugar de eliminarlo.', 'error');

            return back();
        }

        $customer->delete();
        $this->toast('Cliente eliminado.');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'tax_id' => ['nullable', 'string', 'max:30', Rule::unique('customers')->ignore($customer)],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
    }
}
