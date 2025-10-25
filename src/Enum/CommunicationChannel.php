<?php

declare(strict_types=1);

namespace App\Enum;

enum CommunicationChannel: string
{
    case WHATSAPP = 'WHATSAPP';
    case PHONE_CALL = 'PHONE_CALL';
    case IN_APP = 'IN_APP';

    public static function values(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }
}
