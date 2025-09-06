<?php

declare(strict_types=1);

namespace App\Infra\Redis;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class RedisPinger
{
    public function __construct(
        #[Autowire('%env(REDIS_URL)%')] private readonly string $redisDsn,
    ) {
    }

    public function ping(): bool
    {
        $client = RedisAdapter::createConnection($this->redisDsn);

        if (\method_exists($client, 'ping')) {
            $pong = $client->ping();

            return (true === $pong) || (is_string($pong) && str_contains(strtoupper((string) $pong), 'PONG'));
        }

        // Fallback si ping() indisponible
        $client->set('healthz', 'ok', 1);

        return 'ok' === $client->get('healthz');
    }
}
