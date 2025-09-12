<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Status: string implements HasLabel, HasColor
{
    case SCHEDULED = 'scheduled';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SCHEDULED => 'gray',
            self::ACTIVE => 'primary',
            self::COMPLETED => 'success',
        };
    }
}
