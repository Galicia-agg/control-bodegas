<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Models\Movement;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $warehouseIds = Warehouse::accessibleBy($user)->pluck('id');

        $stockQuery = fn () => Stock::query()
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->whereIn('stocks.warehouse_id', $warehouseIds);

        $since = Carbon::today()->subDays(13);

        $daily = Movement::query()
            ->visibleTo($user)
            ->join('movement_items', 'movement_items.movement_id', '=', 'movements.id')
            ->where('occurred_at', '>=', $since)
            ->whereIn('type', [MovementType::Entry, MovementType::Exit])
            ->selectRaw('DATE(occurred_at) as day, type, SUM(movement_items.quantity) as total')
            ->groupBy('day', 'type')
            ->toBase()
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->day)->toDateString());

        $activity = collect(range(0, 13))->map(function (int $offset) use ($since, $daily) {
            $day = $since->copy()->addDays($offset)->toDateString();
            $rows = $daily->get($day, collect());

            return [
                'date' => $day,
                'entries' => (float) $rows->firstWhere('type', MovementType::Entry->value)?->total,
                'exits' => (float) $rows->firstWhere('type', MovementType::Exit->value)?->total,
            ];
        });

        return Inertia::render('Dashboard', [
            'stats' => [
                'products' => Product::where('is_active', true)->count(),
                'warehouses' => $warehouseIds->count(),
                'inventoryValue' => (float) $stockQuery()->sum(DB::raw('stocks.quantity * products.cost')),
                'lowStock' => $stockQuery()->whereColumn('stocks.quantity', '<=', 'products.min_stock')->count(),
                'movementsToday' => Movement::visibleTo($user)->whereDate('occurred_at', today())->count(),
            ],
            'activity' => $activity,
            'recentMovements' => Movement::visibleTo($user)
                ->with(['warehouse:id,code,name', 'destinationWarehouse:id,code,name', 'user:id,name'])
                ->withCount('items')
                ->latest('occurred_at')
                ->latest('id')
                ->limit(6)
                ->get(),
            'lowStock' => $stockQuery()
                ->select('stocks.*')
                ->whereColumn('stocks.quantity', '<=', 'products.min_stock')
                ->with(['product:id,sku,name,unit,min_stock', 'warehouse:id,code,name'])
                ->orderByRaw('stocks.quantity - products.min_stock')
                ->limit(6)
                ->get(),
            'byWarehouse' => Warehouse::whereIn('id', $warehouseIds)
                ->orderBy('name')
                ->get(['id', 'code', 'name'])
                ->map(fn (Warehouse $w) => [
                    'id' => $w->id,
                    'code' => $w->code,
                    'name' => $w->name,
                    'value' => (float) Stock::join('products', 'products.id', '=', 'stocks.product_id')
                        ->where('stocks.warehouse_id', $w->id)
                        ->sum(DB::raw('stocks.quantity * products.cost')),
                ]),
        ]);
    }
}
