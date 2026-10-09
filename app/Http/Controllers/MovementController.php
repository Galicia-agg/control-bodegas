<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Http\Requests\MovementRequest;
use App\Models\Movement;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @phpstan-import-type MovementData from InventoryService
 */
class MovementController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = $request->string('search')->trim()->value();
        $type = $request->enum('type', MovementType::class);
        $warehouse = $request->integer('warehouse') ?: null;

        $movements = Movement::query()
            ->visibleTo($user)
            ->with(['warehouse:id,code,name', 'destinationWarehouse:id,code,name', 'supplier:id,name', 'user:id,name'])
            ->withCount('items')
            ->withSum('items as total_quantity', 'quantity')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->whereLike('folio', "%{$search}%")
                ->orWhereLike('reference', "%{$search}%")))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($warehouse, fn ($q) => $q->where(fn ($q) => $q
                ->where('warehouse_id', $warehouse)
                ->orWhere('destination_warehouse_id', $warehouse)))
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('movements/Index', [
            'movements' => $movements,
            'types' => MovementType::options(),
            'warehouses' => Warehouse::accessibleBy($user)->orderBy('name')->get(['id', 'code', 'name']),
            'filters' => ['search' => $search, 'type' => $type?->value, 'warehouse' => $warehouse],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        $allowedTypes = array_values(array_filter(
            MovementType::cases(),
            fn (MovementType $type) => $user->can($type->permission()),
        ));

        abort_if($allowedTypes === [], 403);

        $warehouses = Warehouse::accessibleBy($user)->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        return Inertia::render('movements/Create', [
            'types' => array_map(fn (MovementType $t) => ['value' => $t->value, 'label' => $t->label()], $allowedTypes),
            'initialType' => ($request->enum('type', MovementType::class) ?? $allowedTypes[0])->value,
            'warehouses' => $warehouses,
            'destinations' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name', 'unit', 'cost']),
            'stock' => Inertia::defer(fn () => Stock::whereIn('warehouse_id', $warehouses->pluck('id'))
                ->get(['warehouse_id', 'product_id', 'quantity'])
                ->groupBy('warehouse_id')
                ->map(fn ($rows) => $rows->pluck('quantity', 'product_id'))),
        ]);
    }

    public function store(MovementRequest $request, InventoryService $inventory): RedirectResponse
    {
        /** @var MovementData $data */
        $data = $request->validated();
        $movement = $inventory->register($data, $request->user());

        $this->toast("Movimiento {$movement->folio} registrado.");

        return to_route('movements.show', $movement);
    }

    public function show(Request $request, Movement $movement): Response
    {
        abort_unless(Movement::visibleTo($request->user())->whereKey($movement->id)->exists(), 403);

        $movement->load(['warehouse:id,code,name', 'destinationWarehouse:id,code,name', 'supplier:id,name,tax_id', 'user:id,name', 'items.product:id,sku,name,unit']);

        return Inertia::render('movements/Show', [
            'movement' => $movement,
            'typeLabel' => $movement->type->label(),
        ]);
    }
}
