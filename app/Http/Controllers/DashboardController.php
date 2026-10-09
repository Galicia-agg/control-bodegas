<?php

namespace App\Http\Controllers;

use App\Enums\StorageUnitStatus;
use App\Models\Customer;
use App\Models\Receipt;
use App\Models\StorageUnit;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $warehouseIds = Warehouse::accessibleBy($user)->pluck('id');

        $storedUnits = fn () => StorageUnit::query()
            ->where('storage_units.status', StorageUnitStatus::Stored)
            ->whereHas('receipt', fn ($q) => $q->whereIn('warehouse_id', $warehouseIds));

        $since = Carbon::today()->subDays(13);

        $daily = Receipt::query()
            ->visibleTo($user)
            ->where('received_at', '>=', $since)
            ->selectRaw('DATE(received_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->toBase()
            ->pluck('total', 'day');

        $activity = collect(range(0, 13))->map(function (int $offset) use ($since, $daily) {
            $day = $since->copy()->addDays($offset)->toDateString();

            return ['date' => $day, 'receipts' => (int) $daily->get($day, 0)];
        });

        return Inertia::render('Dashboard', [
            'stats' => [
                'activeReceipts' => Receipt::visibleTo($user)->where('status', '!=', 'finalizado')->count(),
                'storedUnits' => $storedUnits()->count(),
                'customers' => Customer::where('is_active', true)->count(),
                'receiptsToday' => Receipt::visibleTo($user)->whereDate('received_at', today())->count(),
            ],
            'activity' => $activity,
            'recentReceipts' => Receipt::visibleTo($user)
                ->with(['customer:id,name', 'warehouse:id,code,name'])
                ->withCount('units')
                ->latest('received_at')
                ->latest('id')
                ->limit(6)
                ->get(),
            'byWarehouse' => Warehouse::whereIn('id', $warehouseIds)
                ->orderBy('name')
                ->get(['id', 'code', 'name'])
                ->map(fn (Warehouse $w) => [
                    'id' => $w->id,
                    'code' => $w->code,
                    'name' => $w->name,
                    'units' => StorageUnit::where('status', StorageUnitStatus::Stored)
                        ->whereHas('receipt', fn ($q) => $q->where('warehouse_id', $w->id))
                        ->count(),
                ]),
        ]);
    }
}
