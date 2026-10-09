<?php

namespace App\Enums;

enum MovementType: string
{
    case Entry = 'entrada';
    case Exit = 'salida';
    case Transfer = 'transferencia';
    case Adjustment = 'ajuste';

    public function label(): string
    {
        return match ($this) {
            self::Entry => 'Entrada',
            self::Exit => 'Salida',
            self::Transfer => 'Transferencia',
            self::Adjustment => 'Ajuste',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Entry => 'ENT',
            self::Exit => 'SAL',
            self::Transfer => 'TRF',
            self::Adjustment => 'AJU',
        };
    }

    /**
     * Permission required to register a movement of this type.
     */
    public function permission(): string
    {
        return match ($this) {
            self::Entry => 'movements.entry',
            self::Exit => 'movements.exit',
            self::Transfer => 'movements.transfer',
            self::Adjustment => 'movements.adjust',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
