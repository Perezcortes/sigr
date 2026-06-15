<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LeadCanal: string implements HasLabel
{
    case Nocnok = 'nocnok';
    case Rentas = 'rentas';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Nocnok => 'Nocnok',
            self::Rentas => 'Rentas',
        };
    }
}
