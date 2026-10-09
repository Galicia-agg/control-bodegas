<?php

namespace App\Enums;

enum ReceiptStatus: string
{
    case Active = 'activo';
    case Partial = 'parcial';
    case Finished = 'finalizado';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Almacenado',
            self::Partial => 'Entrega parcial',
            self::Finished => 'Finalizado',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
