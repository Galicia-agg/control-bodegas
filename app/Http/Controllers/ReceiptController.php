<?php

namespace App\Http\Controllers;

use App\Enums\ReceiptStatus;
use App\Enums\StorageUnitStatus;
use App\Enums\StorageUnitType;
use App\Enums\TransportMode;
use App\Http\Requests\ReceiptRequest;
use App\Models\Customer;
use App\Models\Receipt;
use App\Models\Warehouse;
use App\Services\BillingService;
use App\Services\ReceptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @phpstan-import-type ReceiptData from ReceptionService
 */
class ReceiptController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = $request->string('search')->trim()->value();
        $status = $request->enum('status', ReceiptStatus::class);
        $mode = $request->enum('mode', TransportMode::class);
        $warehouse = $request->integer('warehouse') ?: null;

        $receipts = Receipt::query()
            ->visibleTo($user)
            ->with(['customer:id,name', 'warehouse:id,code,name'])
            ->withCount('units')
            ->withCount(['units as stored_units_count' => fn ($q) => $q->where('status', 'almacenado')])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->whereLike('folio', "%{$search}%")
                ->orWhereLike('transport_document', "%{$search}%")
                ->orWhereLike('duca', "%{$search}%")
                ->orWhereHas('customer', fn ($q) => $q->whereLike('name', "%{$search}%"))))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($mode, fn ($q) => $q->where('transport_mode', $mode))
            ->when($warehouse, fn ($q) => $q->where('warehouse_id', $warehouse))
            ->latest('received_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('receipts/Index', [
            'receipts' => $receipts,
            'statuses' => ReceiptStatus::options(),
            'modes' => TransportMode::options(),
            'warehouses' => Warehouse::accessibleBy($user)->orderBy('name')->get(['id', 'code', 'name']),
            'filters' => ['search' => $search, 'status' => $status?->value, 'mode' => $mode?->value, 'warehouse' => $warehouse],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('receipts/Create', [
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(['id', 'name', 'tax_id']),
            'warehouses' => Warehouse::accessibleBy($user)->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'modes' => TransportMode::options(),
            'unitTypes' => StorageUnitType::options(),
        ]);
    }

    public function store(ReceiptRequest $request, ReceptionService $reception): RedirectResponse
    {
        /** @var ReceiptData $data */
        $data = $request->validated();
        $receipt = $reception->register($data, $request->user());

        $this->toast("Ingreso {$receipt->folio} registrado.");

        return to_route('receipts.show', $receipt);
    }

    public function show(Request $request, Receipt $receipt, BillingService $billing): Response
    {
        abort_unless(Receipt::visibleTo($request->user())->whereKey($receipt->id)->exists(), 403);

        $receipt->load([
            'customer:id,name,tax_id,contact_name,phone,email',
            'warehouse:id,code,name',
            'user:id,name',
            'units' => fn ($q) => $q->orderBy('id'),
            'deliveries' => fn ($q) => $q->latest('released_at')->with('voucher:id,delivery_id,folio,total,status'),
        ]);

        return Inertia::render('receipts/Show', [
            'receipt' => $receipt,
            'modeLabel' => $receipt->transport_mode->label(),
            'documentLabel' => $receipt->transport_mode->documentLabel(),
            'statusLabel' => $receipt->status->label(),
            'canDeliver' => $request->user()->can('deliveries.create') && $receipt->units->contains(fn ($u) => $u->status === StorageUnitStatus::Stored),
            'accrual' => $request->user()->can('rates.view') ? $billing->storageAccrual($receipt) : null,
        ]);
    }
}
