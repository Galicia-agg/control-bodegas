<?php

namespace App\Http\Requests;

use App\Enums\MovementType;
use App\Models\Warehouse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = MovementType::tryFrom((string) $this->input('type'));

        return $type === null || $this->user()->can($type->permission());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->input('type');
        $accessible = Warehouse::accessibleBy($this->user())->where('is_active', true)->pluck('id');

        return [
            'type' => ['required', Rule::enum(MovementType::class)],
            'warehouse_id' => ['required', 'integer', Rule::in($accessible)],
            'destination_warehouse_id' => [
                Rule::requiredIf($type === MovementType::Transfer->value),
                'nullable', 'integer', 'different:warehouse_id',
                Rule::exists('warehouses', 'id')->where('is_active', true),
            ],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => [
                'required', 'numeric', 'max:9999999999',
                $type === MovementType::Adjustment->value ? 'min:0' : 'gt:0',
            ],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'warehouse_id' => 'bodega',
            'destination_warehouse_id' => 'bodega destino',
            'supplier_id' => 'proveedor',
            'items' => 'productos',
            'items.*.product_id' => 'producto',
            'items.*.quantity' => 'cantidad',
            'items.*.unit_cost' => 'costo unitario',
            'occurred_at' => 'fecha',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'warehouse_id.in' => 'No tienes acceso a la bodega seleccionada.',
            'destination_warehouse_id.different' => 'La bodega destino debe ser distinta a la de origen.',
            'items.*.product_id.distinct' => 'El producto está repetido en el detalle.',
        ];
    }
}
