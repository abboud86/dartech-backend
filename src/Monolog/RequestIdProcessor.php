<?php

declare(strict_types=1);

namespace App\Monolog;

use App\Observability\RequestIdProvider;
use Monolog\LogRecord;

final class RequestIdProcessor
{
    public function __construct(private readonly RequestIdProvider $provider)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $id = $this->provider->current();
        if (null !== $id && '' !== $id) {
            // bonne pratique: mettre l’identifiant de corrélation dans "extra"
            $record->extra['request_id'] = $id;
        }

        return $record;
    }
}
