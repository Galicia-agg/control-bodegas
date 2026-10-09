<?php

namespace App\Http\Requests;

use App\Enums\StorageUnitType;
use App\Enums\TransportMode;
use App\Models\Warehouse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('receipts.create');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $accessible = Warehouse::accessibleBy($this->user())->where('is_active', true)->pluck('id');

        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'warehouse_id' => ['required', 'integer', Rule::in($accessible)],
            'transport_mode' => ['required', Rule::enum(TransportMode::class)],
            'transport_document' => ['nullable', 'string', 'max:60'],
            'duca' => ['nullable', 'string', 'max:60'],
            'carrier' => ['nullable', 'string', 'max:150'],
            'origin' => ['nullable', 'string', 'max:150'],
            'received_at' => ['nullable', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'units' => ['required', 'array', 'min:1', 'max:300'],
            'units.*.type' => ['required', Rule::enum(StorageUnitType::class)],
            'units.*.parent_index' => ['nullable', 'integer', 'min:0'],
            'units.*.description' => ['nullable', 'string', 'max:255'],
            'units.*.pieces' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
            'units.*.weight_kg' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'units.*.volume_m3' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'units.*.location' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * A parent must be an earlier unit of the list.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ((array) $this->input('units', []) as $index => $unit) {
                $parent = $unit['parent_index'] ?? null;

                if ($parent !== null && $parent !== '' && (int) $parent >= $index) {
                    $validator->errors()->add("units.{$index}.parent_index", 'La unidad contenedora debe estar antes en la lista.');
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'warehouse_id' => 'bodega',
            'transport_mode' => 'tipo de transporte',
            'transport_document' => 'documento de transporte',
            'received_at' => 'fecha de recepción',
            'units' => 'unidades',
            'units.*.type' => 'tipo',
            'units.*.pieces' => 'piezas',
            'units.*.weight_kg' => 'peso',
            'units.*.volume_m3' => 'volumen',
            'units.*.parent_index' => 'unidad contenedora',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'warehouse_id.in' => 'No tienes acceso a la bodega seleccionada.',
        ];
    }
}
