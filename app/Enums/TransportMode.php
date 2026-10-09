<?php

namespace App\Enums;

enum TransportMode: string
{
    case Sea = 'maritimo';
    case Air = 'aereo';
    case Land = 'terrestre';

    public function label(): string
    {
        return match ($this) {
            self::Sea => 'Marítimo',
            self::Air => 'Aéreo',
            self::Land => 'Terrestre',
        };
    }

    /**
     * Name of the transport document for this mode.
     */
    public function documentLabel(): string
    {
        return match ($this) {
            self::Sea => 'BL (Bill of Lading)',
            self::Air => 'Guía aérea (AWB)',
            self::Land => 'Carta de porte',
        };
    }

    /**
     * @return list<array{value: string, label: string, document: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $mode) => ['value' => $mode->value, 'label' => $mode->label(), 'document' => $mode->documentLabel()],
            self::cases(),
        );
    }
}
