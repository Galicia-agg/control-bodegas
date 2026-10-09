<?php

namespace App\Enums;

enum CarrierKind: string
{
    case Own = 'propio';
    case External = 'externo';

    public function label(): string
    {
        return match ($this) {
            self::Own => 'Flota propia',
            self::External => 'Transportista externo',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $k) => ['value' => $k->value, 'label' => $k->label()], self::cases());
    }
}
