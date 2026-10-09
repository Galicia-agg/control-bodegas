<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Enums\DeliveryType;
use App\Enums\PaymentMethod;
use App\Enums\StorageUnitStatus;
use App\Http\Requests\DeliveryRequest;
use App\Models\Carrier;
use App\Models\Delivery;
use App\Models\Receipt;
use App\Services\BillingService;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @phpstan-import-type DeliveryData from DeliveryService
 */
class DeliveryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $status = $request->enum('status', DeliveryStatus::class);
        $type = $request->enum('type', DeliveryType::class);

        $deliveries = Delivery::query()
            ->visibleTo($request->user())
            ->with(['receipt:id,folio,customer_id', 'receipt.customer:id,name', 'carrier:id,name', 'voucher:id,delivery_id,folio,total,status'])
            ->withCount('units')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->whereLike('folio', "%{$search}%")
                ->orWhereLike('recipient_name', "%{$search}%")
                ->orWhereHas('receipt', fn ($q) => $q->whereLike('folio', "%{$search}%"))
                ->orWhereHas('receipt.customer', fn ($q) => $q->whereLike('name', "%{$search}%"))))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest('released_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('deliveries/Index', [
            'deliveries' => $deliveries,
            'statuses' => DeliveryStatus::options(),
            'types' => DeliveryType::options(),
            'filters' => ['search' => $search, 'status' => $status?->value, 'type' => $type?->value],
        ]);
    }

    public function create(Request $request, Receipt $receipt, BillingService $billing): Response|RedirectResponse
    {
        $this->authorizeReceipt($request, $receipt);

        $units = $receipt->units()->orderBy('id')->get();
        $stored = $units->filter(fn ($u) => $u->status === StorageUnitStatus::Stored);

        if ($stored->isEmpty()) {
            $this->toast('Este ingreso ya no tiene mercadería almacenada.', 'error');

            return to_route('receipts.show', $receipt);
        }

        $parents = $units->pluck('parent_id')->filter()->flip();
        $leaves = $stored->filter(fn ($u) => ! $parents->has($u->id));
        $estimates = $billing->estimateByUnit($receipt, $leaves, now());

        $receipt->load('customer:id,name,tax_id');

        return Inertia::render('deliveries/Create', [
            'receipt' => $receipt->only(['id', 'folio', 'customer', 'received_at']),
            'units' => $stored->values()->map(fn ($u) => [
                'id' => $u->id,
                'parent_id' => $u->parent_id,
                'type' => $u->type->value,
                'code' => $u->code,
                'description' => $u->description,
                'location' => $u->location,
                'estimate' => $estimates[$u->id] ?? null,
            ]),
            'carriers' => Carrier::where('is_active', true)->orderBy('name')->get(['id', 'name', 'kind', 'driver_name', 'vehicle_plate']),
            'types' => DeliveryType::options(),
            'paymentMethods' => PaymentMethod::options(),
            'defaultFee' => $billing->deliveryFee($receipt->customer_id),
            'canCollect' => $request->user()->can('vouchers.collect'),
        ]);
    }

    public function store(DeliveryRequest $request, Receipt $receipt, DeliveryService $deliveries): RedirectResponse
    {
        $this->authorizeReceipt($request, $receipt);

        /** @var DeliveryData $data */
        $data = $request->validated();
        $delivery = $deliveries->register($receipt, $data, $request->user());

        $this->toast("Entrega {$delivery->folio} registrada; comprobante {$delivery->voucher?->folio} emitido.");

        return to_route('deliveries.show', $delivery);
    }

    public function show(Request $request, Delivery $delivery): Response
    {
        abort_unless(Delivery::visibleTo($request->user())->whereKey($delivery->id)->exists(), 403);

        $delivery->load([
            'receipt:id,folio,customer_id,warehouse_id',
            'receipt.customer:id,name,tax_id',
            'receipt.warehouse:id,name',
            'carrier:id,name,kind,driver_name,vehicle_plate,phone',
            'user:id,name',
            'units' => fn ($q) => $q->orderBy('storage_units.id'),
            'voucher:id,delivery_id,folio,total,status',
        ]);

        return Inertia::render('deliveries/Show', ['delivery' => $delivery]);
    }

    public function advance(Request $request, Delivery $delivery, DeliveryService $deliveries): RedirectResponse
    {
        abort_unless(Delivery::visibleTo($request->user())->whereKey($delivery->id)->exists(), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in([DeliveryStatus::OnRoute->value, DeliveryStatus::Delivered->value])],
            'recipient_name' => ['nullable', 'string', 'max:150'],
            'proof_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $deliveries->advance($delivery, DeliveryStatus::from($data['status']), $data['recipient_name'] ?? null, $data['proof_note'] ?? null);
        $this->toast('Estado de la entrega actualizado.');

        return back();
    }

    public function cancel(Request $request, Delivery $delivery, DeliveryService $deliveries): RedirectResponse
    {
        abort_unless(Delivery::visibleTo($request->user())->whereKey($delivery->id)->exists(), 403);

        $deliveries->cancel($delivery);
        $this->toast('Entrega cancelada; las unidades volvieron a bodega y el comprobante se anuló.');

        return back();
    }

    private function authorizeReceipt(Request $request, Receipt $receipt): void
    {
        abort_unless(Receipt::visibleTo($request->user())->whereKey($receipt->id)->exists(), 403);
    }
}
