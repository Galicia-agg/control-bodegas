<?php

namespace App\Enums;

enum StorageUnitType: string
{
    case Container = 'contenedor';
    case Pallet = 'palet';
    case Box = 'caja';
    case Package = 'bulto';

    public function label(): string
    {
        return match ($this) {
            self::Container => 'Contenedor',
            self::Pallet => 'Palet',
            self::Box => 'Caja',
            self::Package => 'Bulto',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Container => 'CNT',
            self::Pallet => 'PAL',
            self::Box => 'CAJ',
            self::Package => 'BUL',
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
