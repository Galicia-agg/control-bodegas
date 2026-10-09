<?php

namespace App\Http\Controllers;

use App\Enums\RateService;
use App\Enums\StorageUnitType;
use App\Models\Customer;
use App\Models\Rate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('rates/Index', [
            'rates' => Rate::query()
                ->with('customer:id,name')
                ->orderBy('service')
                ->orderByRaw('customer_id IS NOT NULL')
                ->orderBy('unit_type')
                ->get(),
            'services' => RateService::options(),
            'unitTypes' => StorageUnitType::options(),
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Rate::create($this->validated($request));
        $this->toast('Tarifa creada.');

        return back();
    }

    public function update(Request $request, Rate $rate): RedirectResponse
    {
        $rate->update($this->validated($request));
        $this->toast('Tarifa actualizada.');

        return back();
    }

    public function destroy(Rate $rate): RedirectResponse
    {
        $rate->delete();
        $this->toast('Tarifa eliminada.');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'service' => ['required', Rule::enum(RateService::class)],
            'unit_type' => ['nullable', Rule::enum(StorageUnitType::class)],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'free_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'is_active' => ['boolean'],
        ]);

        $data['free_days'] = $data['service'] === RateService::Storage->value ? ($data['free_days'] ?? 0) : 0;
        $data['unit_type'] = $data['service'] === RateService::Delivery->value ? null : ($data['unit_type'] ?? null);

        return $data;
    }
}
