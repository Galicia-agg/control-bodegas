<?php

namespace App\Http\Requests;

use App\Enums\DeliveryType;
use App\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('deliveries.create');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $home = $this->input('type') === DeliveryType::Home->value;

        return [
            'unit_ids' => ['required', 'array', 'min:1'],
            'unit_ids.*' => ['integer', 'distinct'],
            'type' => ['required', Rule::enum(DeliveryType::class)],
            'recipient_name' => ['required', 'string', 'max:150'],
            'recipient_document' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'carrier_id' => ['nullable', 'integer', Rule::exists('carriers', 'id')->where('is_active', true)],
            'scheduled_at' => ['nullable', 'date'],
            'destination_address' => [Rule::requiredIf($home), 'nullable', 'string', 'max:255'],
            'destination_phone' => ['nullable', 'string', 'max:30'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'paid' => ['boolean'],
            'payment_method' => [Rule::requiredIf((bool) $this->boolean('paid')), 'nullable', Rule::enum(PaymentMethod::class)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->boolean('paid') && ! $this->user()->can('vouchers.collect')) {
                $validator->errors()->add('paid', 'No tienes permiso para registrar cobros.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'unit_ids' => 'unidades',
            'type' => 'tipo de entrega',
            'recipient_name' => 'nombre de quien recibe',
            'recipient_document' => 'documento',
            'carrier_id' => 'transportista',
            'scheduled_at' => 'fecha programada',
            'destination_address' => 'dirección de destino',
            'delivery_fee' => 'costo de entrega',
            'payment_method' => 'forma de pago',
        ];
    }
}
