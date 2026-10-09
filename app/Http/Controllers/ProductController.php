<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MovementItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $category = $request->integer('category') ?: null;

        $products = Product::query()
            ->with('category:id,name')
            ->withSum('stocks as total_stock', 'quantity')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('sku', "%{$search}%")))
            ->when($category, fn ($q) => $q->where('category_id', $category))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('products/Index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'units' => Product::UNITS,
            'filters' => ['search' => $search, 'category' => $category],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::create($this->validated($request));
        $this->toast('Producto creado.');

        return back();
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));
        $this->toast('Producto actualizado.');

        return back();
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->stocks()->where('quantity', '!=', 0)->exists()) {
            $this->toast('No se puede eliminar un producto con existencias; desactívalo.', 'error');

            return back();
        }

        if (MovementItem::where('product_id', $product->id)->exists()) {
            $this->toast('El producto tiene movimientos registrados; desactívalo en lugar de eliminarlo.', 'error');

            return back();
        }

        $product->delete();
        $this->toast('Producto eliminado.');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'sku' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('products')->ignore($product)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit' => ['required', Rule::in(Product::UNITS)],
            'cost' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'min_stock' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'is_active' => ['boolean'],
        ]);
    }
}
