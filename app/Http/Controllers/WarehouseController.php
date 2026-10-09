<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();

        $warehouses = Warehouse::query()
            ->accessibleBy($request->user())
            ->with('manager:id,name')
            ->withCount(['stocks as products_count' => fn ($q) => $q->where('quantity', '>', 0)])
            ->addSelect(['stock_value' => DB::table('stocks')
                ->join('products', 'products.id', '=', 'stocks.product_id')
                ->whereColumn('stocks.warehouse_id', 'warehouses.id')
                ->selectRaw('COALESCE(SUM(stocks.quantity * products.cost), 0)')])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('code', "%{$search}%")))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('warehouses/Index', [
            'warehouses' => $warehouses,
            'managers' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(WarehouseRequest $request): RedirectResponse
    {
        Warehouse::create($request->validated());
        $this->toast('Bodega creada correctamente.');

        return back();
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($request->validated());
        $this->toast('Bodega actualizada.');

        return back();
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->movements()->exists()) {
            $this->toast('La bodega tiene movimientos registrados; desactívala en lugar de eliminarla.', 'error');

            return back();
        }

        $warehouse->delete();
        $this->toast('Bodega eliminada.');

        return back();
    }
}
