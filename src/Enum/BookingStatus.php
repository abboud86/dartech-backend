<?php

declare(strict_types=1);

namespace App\Enum;

enum BookingStatus: string
{
    case INQUIRY = 'INQUIRY';
    case CONTACTED = 'CONTACTED';
    case SCHEDULED = 'SCHEDULED';
    case DONE = 'DONE';
    case CANCELED = 'CANCELED';

    public static function values(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }
}
