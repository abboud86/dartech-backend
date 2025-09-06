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
            $checks['db'] = ($one === 1) ? 'ok' : 'fail';
            if ($checks['db'] !== 'ok') {
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
            if ($checks['redis'] !== 'ok') {
                $statusCode = 503;
            }
        } catch (\Throwable $e) {
            $checks['redis'] = 'fail';
            $checks['redis_error'] = $e->getMessage();
            $statusCode = 503;
        }

        return $this->json(
            ['status' => ($statusCode === 200 ? 'ok' : 'fail'), 'checks' => $checks],
            $statusCode
        );
    }
}
