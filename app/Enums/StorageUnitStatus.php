<?php

namespace App\Enums;

enum StorageUnitStatus: string
{
    case Stored = 'almacenado';
    case Delivered = 'entregado';

    public function label(): string
    {
        return match ($this) {
            self::Stored => 'Almacenado',
            self::Delivered => 'Entregado',
        };
    }
}
