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
            ->withCount(['receipts as active_receipts_count' => fn ($q) => $q->where('status', '!=', 'finalizado')])
            ->addSelect(['stored_units_count' => DB::table('storage_units')
                ->join('receipts', 'receipts.id', '=', 'storage_units.receipt_id')
                ->whereColumn('receipts.warehouse_id', 'warehouses.id')
                ->where('storage_units.status', 'almacenado')
                ->selectRaw('COUNT(*)')])
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
        if ($warehouse->receipts()->exists()) {
            $this->toast('La bodega tiene ingresos registrados; desactívala en lugar de eliminarla.', 'error');

            return back();
        }

        $warehouse->delete();
        $this->toast('Bodega eliminada.');

        return back();
    }
}
