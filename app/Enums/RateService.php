<?php

namespace App\Enums;

enum RateService: string
{
    case Storage = 'almacenaje';
    case Handling = 'manejo';
    case Delivery = 'domicilio';

    public function label(): string
    {
        return match ($this) {
            self::Storage => 'Almacenaje',
            self::Handling => 'Manejo de carga',
            self::Delivery => 'Entrega a domicilio',
        };
    }

    /**
     * How the amount is applied.
     */
    public function basis(): string
    {
        return match ($this) {
            self::Storage => 'por unidad y día',
            self::Handling => 'por unidad entregada',
            self::Delivery => 'por entrega',
        };
    }

    /**
     * @return list<array{value: string, label: string, basis: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->label(), 'basis' => $s->basis()],
            self::cases(),
        );
    }
}
