<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Scheduled = 'programada';
    case OnRoute = 'en_ruta';
    case Delivered = 'entregada';
    case Cancelled = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Programada',
            self::OnRoute => 'En ruta',
            self::Delivered => 'Entregada',
            self::Cancelled => 'Cancelada',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $s) => ['value' => $s->value, 'label' => $s->label()], self::cases());
    }
}
