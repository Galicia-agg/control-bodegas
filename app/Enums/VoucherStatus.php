<?php

namespace App\Enums;

enum VoucherStatus: string
{
    case Pending = 'pendiente';
    case Paid = 'pagado';
    case Voided = 'anulado';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente de pago',
            self::Paid => 'Pagado',
            self::Voided => 'Anulado',
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
