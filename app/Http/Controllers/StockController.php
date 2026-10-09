<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $search = $request->string('search')->trim()->value();
        $warehouse = $request->integer('warehouse') ?: null;
        $category = $request->integer('category') ?: null;
        $lowOnly = $request->boolean('low');

        $warehouses = Warehouse::accessibleBy($user)->orderBy('name')->get(['id', 'code', 'name']);

        $stocks = Stock::query()
            ->select('stocks.*')
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->with(['product:id,sku,name,unit,cost,min_stock,category_id', 'product.category:id,name', 'warehouse:id,code,name'])
            ->whereIn('stocks.warehouse_id', $warehouses->pluck('id'))
            ->when($warehouse, fn ($q) => $q->where('stocks.warehouse_id', $warehouse))
            ->when($category, fn ($q) => $q->where('products.category_id', $category))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->whereLike('products.name', "%{$search}%")
                ->orWhereLike('products.sku', "%{$search}%")))
            ->when($lowOnly, fn ($q) => $q->whereColumn('stocks.quantity', '<=', 'products.min_stock'))
            ->orderBy('products.name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('stock/Index', [
            'stocks' => $stocks,
            'warehouses' => $warehouses,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'warehouse' => $warehouse,
                'category' => $category,
                'low' => $lowOnly,
            ],
        ]);
    }
}
