<?php

namespace App\Enums;

enum DeliveryType: string
{
    case Pickup = 'retiro';
    case Home = 'domicilio';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Retiro en bodega',
            self::Home => 'Entrega a domicilio',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $t) => ['value' => $t->value, 'label' => $t->label()], self::cases());
    }
}
