<?php

namespace App\Http\Controllers;

use App\Enums\CarrierKind;
use App\Models\Carrier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CarrierController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('carriers/Index', [
            'carriers' => Carrier::query()->withCount('deliveries')->orderBy('name')->get(),
            'kinds' => CarrierKind::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Carrier::create($this->validated($request));
        $this->toast('Transportista creado.');

        return back();
    }

    public function update(Request $request, Carrier $carrier): RedirectResponse
    {
        $carrier->update($this->validated($request));
        $this->toast('Transportista actualizado.');

        return back();
    }

    public function destroy(Carrier $carrier): RedirectResponse
    {
        if ($carrier->deliveries()->exists()) {
            $this->toast('El transportista tiene entregas registradas; desactívalo en lugar de eliminarlo.', 'error');

            return back();
        }

        $carrier->delete();
        $this->toast('Transportista eliminado.');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'kind' => ['required', Rule::enum(CarrierKind::class)],
            'driver_name' => ['nullable', 'string', 'max:150'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ]);
    }
}
