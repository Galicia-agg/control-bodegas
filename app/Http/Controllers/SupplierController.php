<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();

        return Inertia::render('suppliers/Index', [
            'suppliers' => Supplier::query()
                ->withCount('movements')
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

    public function store(Request $request): RedirectResponse
    {
        Supplier::create($this->validated($request));
        $this->toast('Proveedor creado.');

        return back();
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request, $supplier));
        $this->toast('Proveedor actualizado.');

        return back();
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();
        $this->toast('Proveedor eliminado.');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'tax_id' => ['nullable', 'string', 'max:30', Rule::unique('suppliers')->ignore($supplier)],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ]);
    }
}
