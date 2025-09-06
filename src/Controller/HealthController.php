<?php

declare(strict_types=1);

namespace App\Controller;

use App\Infra\Redis\RedisPinger;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class HealthController extends AbstractController
{
    #[Route('/healthz', name: 'app.healthz', methods: ['GET', 'HEAD'])]
    public function healthz(Connection $db, RedisPinger $redis): JsonResponse
    {
        $checks = [];
        $statusCode = 200;

        // DB
        try {
            $one = (int) $db->fetchOne('SELECT 1');
            $checks['db'] = (1 === $one) ? 'ok' : 'fail';
            if ('ok' !== $checks['db']) {
                $statusCode = 503;
            }
        } catch (\Throwable $e) {
            $checks['db'] = 'fail';
            $checks['db_error'] = $e->getMessage();
            $statusCode = 503;
        }

        // Redis
        try {
            $checks['redis'] = $redis->ping() ? 'ok' : 'fail';
            if ('ok' !== $checks['redis']) {
                $statusCode = 503;
            }
        } catch (\Throwable $e) {
            $checks['redis'] = 'fail';
            $checks['redis_error'] = $e->getMessage();
            $statusCode = 503;
        }

        return $this->json(
            ['status' => (200 === $statusCode ? 'ok' : 'fail'), 'checks' => $checks],
            $statusCode
        );
    }
}
