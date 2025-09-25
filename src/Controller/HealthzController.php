<?php

namespace App\Controller;

use Doctrine\DBAL\Connection as DbalConnection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthzController extends AbstractController
{
    #[Route('/healthz', name: 'healthz', methods: ['GET'])]
    public function __invoke(?DbalConnection $db = null): JsonResponse
    {
        $dbUp    = $this->checkDb($db);
        $redisUp = $this->checkRedis($_ENV['REDIS_URL'] ?? 'redis://127.0.0.1:6379');

        $allUp = $dbUp && $redisUp;
        $payload = [
            'status' => $allUp ? 'ok' : 'degraded',
            'checks' => [
                'db'    => $dbUp ? 'up' : 'down',
                'redis' => $redisUp ? 'up' : 'down',
            ],
            'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];

        return new JsonResponse(
            $payload,
            $allUp ? JsonResponse::HTTP_OK : JsonResponse::HTTP_SERVICE_UNAVAILABLE,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    private function checkDb(?DbalConnection $db): bool
    {
        try {
            if ($db) {
                $db->executeQuery('SELECT 1');
                return true;
            }
        } catch (\Throwable $e) {
            // ignore, fallback below
        }
        // Fallback TCP si extension/driver indispo
        return $this->tcpPing('127.0.0.1', 5432, 0.8);
    }

    private function checkRedis(string $dsn): bool
    {
        // Essai phpredis si présent
        if (class_exists(\Redis::class)) {
            try {
                $url  = parse_url($dsn) ?: [];
                $host = $url['host'] ?? '127.0.0.1';
                $port = (int)($url['port'] ?? 6379);
                $r = new \Redis();
                if ($r->connect($host, $port, 0.8)) {
                    $pong = $r->ping();
                    return $pong === '+PONG' || $pong === 'PONG' || $pong === true;
                }
            } catch (\Throwable $e) {
                // ignore, fallback below
            }
        }
        // Fallback TCP
        $url  = parse_url($dsn) ?: [];
        $host = $url['host'] ?? '127.0.0.1';
        $port = (int)($url['port'] ?? 6379);
        return $this->tcpPing($host, $port, 0.8);
    }

    private function tcpPing(string $host, int $port, float $timeout): bool
    {
        try {
            $s = @fsockopen($host, $port, $errno, $errstr, $timeout);
            if ($s) { fclose($s); return true; }
        } catch (\Throwable $e) {
            // ignore
        }
        return false;
    }
}
