<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();

        return Inertia::render('customers/Index', [
            'customers' => Customer::query()
                ->withCount('receipts')
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
        Customer::create($this->validated($request));
        $this->toast('Cliente creado.');

        return back();
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request, $customer));
        $this->toast('Cliente actualizado.');

        return back();
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->receipts()->exists()) {
            $this->toast('El cliente tiene ingresos registrados; desactívalo en lugar de eliminarlo.', 'error');

            return back();
        }

        $customer->delete();
        $this->toast('Cliente eliminado.');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'tax_id' => ['nullable', 'string', 'max:30', Rule::unique('customers')->ignore($customer)],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
    }
}
